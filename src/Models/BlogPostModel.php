<?php

require_once __DIR__ . '/../Helpers/Uuid.php';

class BlogPostModel
{
    private $conn;
    private $table = 'blog_posts';

    public $id_post;
    public $author_id;
    public $title;
    public $content_markdown;
    public $excerpt;
    public $cover_image_url;
    public $status;
    public $likes_count;
    public $published_at;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get all posts with optional filters
     * @param array $filters ['status' => 'published', 'author_id' => 'uuid', 'tag_id' => 1]
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll($filters = [], $limit = 10, $offset = 0)
    {
        $query = "SELECT 
                    p.*,
                    u.name as author_name,
                    u.email as author_email
                  FROM " . $this->table . " p
                  INNER JOIN users u ON p.author_id = u.id_user
                  WHERE 1=1";

        $params = [];

        // Apply filters
        if (isset($filters['status'])) {
            $query .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (isset($filters['author_id'])) {
            $query .= " AND p.author_id = :author_id";
            $params[':author_id'] = $filters['author_id'];
        }

        // Order by published date (most recent first)
        $query .= " ORDER BY p.published_at DESC, p.created_at DESC";

        // Pagination
        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        // Bind filter params
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        // Bind pagination params
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get single post by ID
     */
    public function getById($id)
    {
        $query = "SELECT 
                    p.*,
                    u.name as author_name,
                    u.email as author_email
                  FROM " . $this->table . " p
                  INNER JOIN users u ON p.author_id = u.id_user
                  WHERE p.id_post = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Create new post
     */
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (id_post, author_id, title, content_markdown, excerpt, cover_image_url, status, created_at, updated_at) 
                  VALUES (:id_post, :author_id, :title, :content_markdown, :excerpt, :cover_image_url, :status, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);

        // Generate UUID
        $uuid = Uuid::generate();

        // Default status to draft if not provided
        $status = $data['status'] ?? 'draft';

        $stmt->bindParam(':id_post', $uuid);
        $stmt->bindParam(':author_id', $data['author_id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':content_markdown', $data['content_markdown']);
        $stmt->bindParam(':excerpt', $data['excerpt']);
        $stmt->bindParam(':cover_image_url', $data['cover_image_url']);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            return $uuid;
        }

        return false;
    }

    /**
     * Update post
     */
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table . " 
                  SET title = :title,
                      content_markdown = :content_markdown,
                      excerpt = :excerpt,
                      cover_image_url = :cover_image_url,
                      updated_at = NOW()
                  WHERE id_post = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':content_markdown', $data['content_markdown']);
        $stmt->bindParam(':excerpt', $data['excerpt']);
        $stmt->bindParam(':cover_image_url', $data['cover_image_url']);

        return $stmt->execute();
    }

    /**
     * Delete post
     */
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id_post = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Publish a draft post
     */
    public function publish($id)
    {
        $query = "UPDATE " . $this->table . " 
                  SET status = 'published',
                      published_at = NOW(),
                      updated_at = NOW()
                  WHERE id_post = :id AND status = 'draft'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    /**
     * Check if user is the author of the post
     */
    public function isAuthor($postId, $userId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE id_post = :post_id AND author_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Get posts by tag
     */
    public function getByTag($tagId, $limit = 10, $offset = 0)
    {
        $query = "SELECT 
                    p.*,
                    u.name as author_name,
                    u.email as author_email
                  FROM " . $this->table . " p
                  INNER JOIN users u ON p.author_id = u.id_user
                  INNER JOIN post_tags pt ON p.id_post = pt.post_id
                  WHERE pt.tag_id = :tag_id AND p.status = 'published'
                  ORDER BY p.published_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get tags for a specific post
     */
    public function getTags($postId)
    {
        $query = "SELECT t.* 
                  FROM tags t
                  INNER JOIN post_tags pt ON t.id_tag = pt.tag_id
                  WHERE pt.post_id = :post_id
                  ORDER BY t.name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Associate tags with a post
     */
    public function attachTags($postId, $tagIds)
    {
        // First, remove existing tags
        $deleteQuery = "DELETE FROM post_tags WHERE post_id = :post_id";
        $deleteStmt = $this->conn->prepare($deleteQuery);
        $deleteStmt->bindParam(':post_id', $postId);
        $deleteStmt->execute();

        // Then, add new tags
        if (!empty($tagIds)) {
            $insertQuery = "INSERT INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)";
            $insertStmt = $this->conn->prepare($insertQuery);

            foreach ($tagIds as $tagId) {
                $insertStmt->bindParam(':post_id', $postId);
                $insertStmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
                $insertStmt->execute();
            }
        }

        return true;
    }

    /**
     * Increment total_posts counter for user (called after publish)
     */
    public function incrementUserPostsCount($userId)
    {
        $query = "UPDATE users SET total_posts = total_posts + 1 WHERE id_user = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }

    /**
     * Decrement total_posts counter for user (called after delete published post)
     */
    public function decrementUserPostsCount($userId)
    {
        $query = "UPDATE users SET total_posts = total_posts - 1 WHERE id_user = :user_id AND total_posts > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }
}
