<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/BoardItem.php';
require_once __DIR__ . '/../Middlewares/ApiKeyAuth.php';
class BoardController {

    private $db;
    private $conn;
    private $boardItem;
    private $apiKeyData;

    private function __construct(){
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->boardItem = new BoardItem($this->conn);
    }

    private function authenticate($requiredPermission = null) {
        $this->apiKeyData = requireApiKey();
        if ($requiredPermission) {
            requirePermission($this->apiKeyData, $requiredPermission);
        }
    }

    public function index($id = null) {
        $this->authenticate('read'); 

        try {
            $items = $this->boardItem->getAll();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $items,
                'count' => count($items)
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error' . $e->getMessage() 
            ]);
        }

    }










}