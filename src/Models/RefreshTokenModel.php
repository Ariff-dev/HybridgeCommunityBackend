<?php

require_once __DIR__ . '/../Config/database.php';

class RefreshTokenModel {
    private $conn;
    private $table = 'refresh_tokens';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new refresh token
     * 
     * @param string $userId User ID
     * @param string $token Refresh token
     * @param int $expiresAt Expiry timestamp
     * @return bool Success
     */
    public function create($userId, $token, $expiresAt) {
        $query = "INSERT INTO {$this->table} (user_id, token, expires_at) 
                  VALUES (:user_id, :token, FROM_UNIXTIME(:expires_at))";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);

        return $stmt->execute();
    }

    /**
     * Validate refresh token
     * 
     * @param string $token Refresh token
     * @return array|false Token data or false if invalid
     */
    public function validate($token) {
        $query = "SELECT id, user_id, expires_at, revoked 
                  FROM {$this->table} 
                  WHERE token = :token 
                  AND revoked = 0 
                  AND expires_at > NOW() 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $result = $stmt->fetch();

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Revoke a refresh token
     * 
     * @param string $token Refresh token
     * @return bool Success
     */
    public function revoke($token) {
        $query = "UPDATE {$this->table} 
                  SET revoked = 1 
                  WHERE token = :token";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);

        return $stmt->execute();
    }

    /**
     * Revoke all refresh tokens for a user
     * 
     * @param string $userId User ID
     * @return bool Success
     */
    public function revokeAllForUser($userId) {
        $query = "UPDATE {$this->table} 
                  SET revoked = 1 
                  WHERE user_id = :user_id 
                  AND revoked = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);

        return $stmt->execute();
    }

    /**
     * Delete expired tokens (cleanup)
     * 
     * @return bool Success
     */
    public function deleteExpired() {
        $query = "DELETE FROM {$this->table} 
                  WHERE expires_at < NOW() 
                  OR revoked = 1";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute();
    }

    /**
     * Get all active tokens for a user
     * 
     * @param string $userId User ID
     * @return array Tokens
     */
    public function getActiveTokensForUser($userId) {
        $query = "SELECT id, token, expires_at, created_at 
                  FROM {$this->table} 
                  WHERE user_id = :user_id 
                  AND revoked = 0 
                  AND expires_at > NOW() 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
