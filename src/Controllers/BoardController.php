<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Middlewares/ApiKeyAuth.php';
class BoardController {

    private $db;
    private $conn;
    private $apiKeyData;

    private function __construct(){
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    private function authenticate($requiredPermission = null) {
        $this->apiKeyData = requireApiKey();
        if ($requiredPermission) {
            requirePermission($this->apiKeyData, $requiredPermission);
        }
    }






}