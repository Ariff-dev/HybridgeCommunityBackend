<?php

require_once __DIR__ . '/../Models/UserModel.php';

class UserController
{
    private $userModel;
    private $db;
    private $conn;


    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->userModel = new UserModel($this->conn);
    }




    public function getAll()
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

        try {
            $result = $this->userModel->getAll();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getUserByEmail($email)
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
        try {
            $result = $this->userModel->getUserByEmail($email);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function createUser($data)
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
        try {
            $result = $this->userModel->createUser($data);
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}