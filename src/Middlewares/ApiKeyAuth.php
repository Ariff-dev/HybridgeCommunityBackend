<?php

require_once __DIR__ . '/../Config/database.php';

class ApiKeyAuth {
    private $db;
    private $conn;

    public function __construct(){
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }


    public function authenticate() {
        $apiKey = $this->getApiKeyFromRequest();

        if (!$apiKey) {
            $this->sendError(401, 'API KEY, is required');
            return false;
        }
        $keyData = $this->validateApiKey($apiKey);

        $this->updateLastUsed($keyData['id']);
        return $keyData;
    }

    private function getApiKeyFromRequest() {
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }
        return null;
    }

    private function validateApiKey($apiKey) {
        try {
            $query = "SELECT id, key_value, name, permissions, is_active, expires_at
                      FROM api_keys
                      WHERE key_value = :key
                      AND is_active = 1
                      AND (expires IS NULL OR expires_at -> NOW();
                      LIMIT 1;
                    ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':key',$apiKey);
            $stmt->execute();

            $keyData = $stmt->fetch();

            if ($keyData) {
                $keyData['permissions'] = json_decode($keyData['permissions'],true);
            }

            return $keyData ?: false;

        } catch (PDOException $e) {
            error_log('Error to validate ApiKey'. $e->getMessage());
            return false;
        }
    }


    private function updateLastUsed($keyId) {
        try {
            $query = "UPDATE api_keys SET last_used_at = NOW() WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $keyId);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log('Update error' . $e->getMessage());
        }
    }

    public function hasPermission($keyData, $permission) {
          if (!isset($keyData['permissions']) || empty($keyData['permissions'])) {
            return false;
        }
        
        return in_array($permission, $keyData['permissions']);
    }

        private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'error' => true,
            'message' => $message
        ]);
        exit();
    }


}

/**
 * Helper functions for controllers
 */
function requireApiKey() {
    $auth = new ApiKeyAuth();
    $keyData = $auth->authenticate();
    
    if (!$keyData) {
        exit(); // Ya envió el error
    }
    
    return $keyData;
}

/**
 * Helper to validate permissions
 */
function requirePermission($keyData, $permission) {
    $auth = new ApiKeyAuth();
    
    if (!$auth->hasPermission($keyData, $permission)) {
        http_response_code(403);
        echo json_encode([
            'error' => true,
            'message' => "Permiso requerido: $permission"
        ]);
        exit();
    }
}