<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Models/BoardItem.php';
require_once __DIR__ . '/../Middlewares/ApiKeyAuth.php';
class BoardController {

    private $db;
    private $conn;
    private $boardItem;
    private $apiKeyData;

    public function __construct(){
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

    public function store($id = null) {
        $this->authenticate('write');

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['name'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Name is required'
            ]);
            return;
        }

        try  {
            $this->boardItem->name = $data['name'];
            $this->boardItem->description = $data['description'];
            $this->boardItem->state = $data['state'];
            $this->boardItem->assigned = $data['assigned'];

            if ($this->boardItem->create()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Item created correctly',
                ]);
            } else {
                throw new Exception('Error creating item');
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update($id = null) {
        $this->authenticate('write');

        $data = json_decode(file_get_contents("php://input"),true);

        if (!isset($data['id_board'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Expected items not found'
            ]);
            return;
        }

        try {
            $this->boardItem->id_board = $data['id_board'];
            $this->boardItem->name = $data['name'];
            $this->boardItem->description = $data['description'];
            $this->boardItem->state = $data['state'];
            $this->boardItem->assigned = $data['assigned'];

            if ($this->boardItem->update()) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Item updated successfully'
                ]);
            } else {
                throw new Exception('Error updating item');
            }
        } catch (PDOException $e) {
            http_response_code(500);
              echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }










}