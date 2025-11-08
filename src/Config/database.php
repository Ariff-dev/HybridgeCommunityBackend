<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn;

    public function __construct(){
        $this->host = $_ENV['DB_HOST'];
        $this->db_name = $_ENV['DB_NAME'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASS'];
        $this->charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo json_encode([
                'error' => true,
                'message' => 'Error de conexión' . $e->getMessage()
            ]);
            exit();
        }

        return $this->conn;
    }

}