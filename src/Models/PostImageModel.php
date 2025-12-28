<?php

class PostImageModel
{
    private $conn;
    private $table = 'post_images';

    public $id_image;
    public $post_id;
    public $image_url;
    public $cloudinary_id;
    public $alt_text;
    public $position;
    public $uploaded_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Get all images for a post
     */
    public function getByPostId($postId)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE post_id = :post_id 
                  ORDER BY position ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Create new image record
     */
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (post_id, image_url, cloudinary_id, alt_text, position, uploaded_at) 
                  VALUES (:post_id, :image_url, :cloudinary_id, :alt_text, :position, NOW())";

        $stmt = $this->conn->prepare($query);

        $position = $data['position'] ?? 1;
        $altText = $data['alt_text'] ?? '';

        $stmt->bindParam(':post_id', $data['post_id']);
        $stmt->bindParam(':image_url', $data['image_url']);
        $stmt->bindParam(':cloudinary_id', $data['cloudinary_id']);
        $stmt->bindParam(':alt_text', $altText);
        $stmt->bindValue(':position', $position, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        return false;
    }

    /**
     * Create multiple images for a post
     */
    public function createMultiple($postId, $images)
    {
        $position = 1;
        $insertedIds = [];

        foreach ($images as $image) {
            $data = [
                'post_id' => $postId,
                'image_url' => $image['url'],
                'cloudinary_id' => $image['cloudinary_id'],
                'alt_text' => $image['alt_text'] ?? '',
                'position' => $image['position'] ?? $position
            ];

            $id = $this->create($data);
            if ($id) {
                $insertedIds[] = $id;
            }

            $position++;
        }

        return $insertedIds;
    }

    /**
     * Delete image
     */
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id_image = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Delete all images for a post
     */
    public function deleteByPostId($postId)
    {
        $query = "DELETE FROM " . $this->table . " WHERE post_id = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);

        return $stmt->execute();
    }

    /**
     * Get image by ID
     */
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id_image = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Get Cloudinary IDs for a post (useful for bulk deletion)
     */
    public function getCloudinaryIdsByPostId($postId)
    {
        $query = "SELECT cloudinary_id FROM " . $this->table . " WHERE post_id = :post_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $postId);
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_column($results, 'cloudinary_id');
    }
}
