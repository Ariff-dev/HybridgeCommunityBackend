<?php

require_once __DIR__ . '/../Helpers/Uuid.php';

class UserModel {
    private $conn;
    private $table = 'users';

    public $id_user;
    public $name;
    public $email;
    public $password;
    public $created_at;
    public $updated_at;


    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT id_user, name, email, created_at, updated_at FROM " . $this->table;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getUserByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function getUserById($id) {
        $query = "SELECT id_user, name, email, created_at, updated_at FROM " . $this->table . " WHERE id_user = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch();
    }

    public function createUser($data) {
        $query = "INSERT INTO " . $this->table . " (id_user, name, email, password, created_at, updated_at) 
                  VALUES (:id_user, :name, :email, :password, NOW(), NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Generate UUID
        $uuid = Uuid::generate();
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $stmt->bindParam(':id_user', $uuid);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashedPassword);
        
        if ($stmt->execute()) {
            return $uuid;
        }
        
        return false;
    }

    public function updateUser($id, $data) {
        $query = "UPDATE " . $this->table . " 
                  SET name = :name, 
                      email = :email, 
                      updated_at = NOW() 
                  WHERE id_user = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);

        return $stmt->execute();
    }

}