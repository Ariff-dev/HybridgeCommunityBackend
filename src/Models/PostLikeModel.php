<?php

class PostLikeModel
{
    private $conn;
    private $table = 'post_likes';

    public $id_like;
    public $post_id;
    public $user_id;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Check if user has liked a post
     */
    public function hasLiked($postId, $userId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE post_id = :post_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Add like to a post
     */
    public function like($postId, $userId)
    {
        try {
            // Check if already liked
            if ($this->hasLiked($postId, $userId)) {
                return false; // Already liked
            }

            // Insert like
            $query = "INSERT INTO " . $this->table . " (post_id, user_id, created_at) 
                      VALUES (:post_id, :user_id, NOW())";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':post_id', $postId);
            $stmt->bindParam(':user_id', $userId);

            if ($stmt->execute()) {
                // Update likes_count in blog_posts (trigger should handle this, but doing it explicitly)
                $this->updatePostLikesCount($postId);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            // Handle duplicate entry error gracefully
            if ($e->getCode() == 23000) { // Integrity constraint violation
                return false;
            }
            throw $e;
        }
    }

    /**
     * Remove like from a post
     */
    public function unlike($postId, $userId)
    {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE post_id = :post_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindParam(':user_id', $userId);

        if ($stmt->execute()) {
            // Update likes_count in blog_posts
            $this->updatePostLikesCount($postId);
            return $stmt->rowCount() > 0; // Return true if a row was actually deleted
        }

        return false;
    }

    /**
     * Toggle like (like if not liked, unlike if already liked)
     */
    public function toggle($postId, $userId)
    {
        if ($this->hasLiked($postId, $userId)) {
            return [
                'action' => 'unliked',
                'success' => $this->unlike($postId, $userId)
            ];
        } else {
            return [
                'action' => 'liked',
                'success' => $this->like($postId, $userId)
            ];
        }
    }

    /**
     * Get total likes for a post
     */
    public function getCountByPostId($postId)
    {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE post_id = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();

        $result = $stmt->fetch();
        return (int) $result['count'];
    }

    /**
     * Get users who liked a post
     */
    public function getUsersByPostId($postId, $limit = 50)
    {
        $query = "SELECT u.id_user, u.name, u.email, pl.created_at as liked_at
                  FROM " . $this->table . " pl
                  INNER JOIN users u ON pl.user_id = u.id_user
                  WHERE pl.post_id = :post_id
                  ORDER BY pl.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update likes_count in blog_posts table
     * This ensures the denormalized counter is always accurate
     */
    private function updatePostLikesCount($postId)
    {
        $query = "UPDATE blog_posts SET likes_count = (
                    SELECT COUNT(*) FROM " . $this->table . " WHERE post_id = :post_id
                  ) WHERE id_post = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);

        return $stmt->execute();
    }

    /**
     * Get posts liked by a user
     */
    public function getPostsByUserId($userId, $limit = 20)
    {
        $query = "SELECT p.*, pl.created_at as liked_at
                  FROM " . $this->table . " pl
                  INNER JOIN blog_posts p ON pl.post_id = p.id_post
                  WHERE pl.user_id = :user_id
                  ORDER BY pl.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
