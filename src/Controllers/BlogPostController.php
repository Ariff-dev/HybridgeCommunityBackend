<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/BlogPostModel.php';
require_once __DIR__ . '/../Models/PostImageModel.php';
require_once __DIR__ . '/../Models/PostLikeModel.php';

class BlogPostController
{
    private $db;
    private $conn;
    private $blogPostModel;
    private $postImageModel;
    private $postLikeModel;

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->blogPostModel = new BlogPostModel($this->conn);
        $this->postImageModel = new PostImageModel($this->conn);
        $this->postLikeModel = new PostLikeModel($this->conn);
    }

    /**
     * Get all posts with filtering and pagination
     * GET /api/blog/posts?status=published&page=1&limit=10
     */
    public function index()
    {
        try {
            // Get query parameters
            $status = $_GET['status'] ?? 'published';
            $authorId = $_GET['author_id'] ?? null;
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 10;
            $offset = ($page - 1) * $limit;

            // Build filters
            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }
            if ($authorId) {
                $filters['author_id'] = $authorId;
            }

            // Get posts
            $posts = $this->blogPostModel->getAll($filters, $limit, $offset);

            // Add tags to each post
            foreach ($posts as &$post) {
                $post['tags'] = $this->blogPostModel->getTags($post['id_post']);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $posts,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'count' => count($posts)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to fetch posts'
            ]);
        }
    }

    /**
     * Get single post by ID
     * GET /api/blog/posts/:id
     */
    public function show($id)
    {
        try {
            $post = $this->blogPostModel->getById($id);

            if (!$post) {
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'Post not found'
                ]);
                return;
            }

            // Add tags and images
            $post['tags'] = $this->blogPostModel->getTags($id);
            $post['images'] = $this->postImageModel->getByPostId($id);

            // Check if current user has liked (if authenticated)
            global $currentUser;
            if ($currentUser) {
                $post['has_liked'] = $this->postLikeModel->hasLiked($id, $currentUser['user_id']);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $post
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to fetch post'
            ]);
        }
    }

    /**
     * Create new post
     * POST /api/blog/posts
     */
    public function store()
    {
        global $currentUser;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (!isset($data['title']) || !isset($data['content_markdown'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Title and content are required'
            ]);
            return;
        }

        // Validate title length
        if (strlen($data['title']) > 200) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Title must be less than 200 characters'
            ]);
            return;
        }

        try {
            // Prepare post data
            $postData = [
                'author_id' => $currentUser['user_id'],
                'title' => $data['title'],
                'content_markdown' => $data['content_markdown'],
                'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content_markdown']), 0, 300),
                'cover_image_url' => $data['cover_image_url'] ?? null,
                'status' => $data['status'] ?? 'draft'
            ];

            // Create post
            $postId = $this->blogPostModel->create($postData);

            if (!$postId) {
                throw new Exception('Failed to create post');
            }

            // Add images if provided
            if (isset($data['images']) && is_array($data['images'])) {
                $this->postImageModel->createMultiple($postId, $data['images']);
            }

            // Attach tags if provided
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $this->blogPostModel->attachTags($postId, $data['tag_ids']);
            }

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => [
                    'id_post' => $postId
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to create post'
            ]);
        }
    }

    /**
     * Update post
     * PUT /api/blog/posts/:id
     */
    public function update($id)
    {
        global $currentUser;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        // Check if post exists
        $post = $this->blogPostModel->getById($id);
        if (!$post) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Post not found'
            ]);
            return;
        }

        // Check if user is the author
        if (!$this->blogPostModel->isAuthor($id, $currentUser['user_id'])) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'You are not authorized to update this post'
            ]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (!isset($data['title']) || !isset($data['content_markdown'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Title and content are required'
            ]);
            return;
        }

        try {
            $postData = [
                'title' => $data['title'],
                'content_markdown' => $data['content_markdown'],
                'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content_markdown']), 0, 300),
                'cover_image_url' => $data['cover_image_url'] ?? $post['cover_image_url']
            ];

            $updated = $this->blogPostModel->update($id, $postData);

            if (!$updated) {
                throw new Exception('Failed to update post');
            }

            // Update tags if provided
            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $this->blogPostModel->attachTags($id, $data['tag_ids']);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Post updated successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to update post'
            ]);
        }
    }

    /**
     * Delete post
     * DELETE /api/blog/posts/:id
     */
    public function delete($id)
    {
        global $currentUser;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        // Check if post exists
        $post = $this->blogPostModel->getById($id);
        if (!$post) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Post not found'
            ]);
            return;
        }

        // Check if user is the author
        if (!$this->blogPostModel->isAuthor($id, $currentUser['user_id'])) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'You are not authorized to delete this post'
            ]);
            return;
        }

        try {
            // Get Cloudinary IDs before deletion (for cleanup)
            $cloudinaryIds = $this->postImageModel->getCloudinaryIdsByPostId($id);

            // Delete post (cascade will delete images, likes, tags automatically)
            $deleted = $this->blogPostModel->delete($id);

            if (!$deleted) {
                throw new Exception('Failed to delete post');
            }

            // Decrement user's post count if it was published
            if ($post['status'] === 'published') {
                $this->blogPostModel->decrementUserPostsCount($post['author_id']);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Post deleted successfully',
                'cloudinary_ids' => $cloudinaryIds // Frontend should delete these from Cloudinary
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to delete post'
            ]);
        }
    }

    /**
     * Publish a draft post
     * POST /api/blog/posts/:id/publish
     */
    public function publish($id)
    {
        global $currentUser;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        // Check if post exists
        $post = $this->blogPostModel->getById($id);
        if (!$post) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Post not found'
            ]);
            return;
        }

        // Check if user is the author
        if (!$this->blogPostModel->isAuthor($id, $currentUser['user_id'])) {
            http_response_code(403);
            echo json_encode([
                'error' => true,
                'message' => 'You are not authorized to publish this post'
            ]);
            return;
        }

        // Check if already published
        if ($post['status'] === 'published') {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Post is already published'
            ]);
            return;
        }

        try {
            $published = $this->blogPostModel->publish($id);

            if (!$published) {
                throw new Exception('Failed to publish post');
            }

            // Increment user's total posts count
            $this->blogPostModel->incrementUserPostsCount($currentUser['user_id']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Post published successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to publish post'
            ]);
        }
    }

    /**
     * Toggle like on a post
     * POST /api/blog/posts/:id/like
     */
    public function toggleLike($id)
    {
        global $currentUser;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        // Check if post exists
        $post = $this->blogPostModel->getById($id);
        if (!$post) {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Post not found'
            ]);
            return;
        }

        try {
            $result = $this->postLikeModel->toggle($id, $currentUser['user_id']);

            if (!$result['success']) {
                throw new Exception('Failed to toggle like');
            }

            // Get updated like count
            $likeCount = $this->postLikeModel->getCountByPostId($id);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => ucfirst($result['action']),
                'data' => [
                    'action' => $result['action'],
                    'likes_count' => $likeCount
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to toggle like'
            ]);
        }
    }
}
