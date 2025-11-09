<?php

class BoardItem {
    private $conn;
    private $table = 'board';

    public $id_board;
    public $name;
    public $description;
    public $state;
    public $create_at;
    public $assigned;


    public function __construct($db){
        $this->conn = $db;
    }

    // GET
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY create_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id_board = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id',$id,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByState($state) {
        $query = "SELECT * FROM {$this->table} WHERE state = :state ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':state', $state);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //CREATE

    public function create() {
        $query = "INSERT INTO {$this->table}
                  SET name = :name,
                      description = :description ,
                      state = :state,
                      assigned = :assigned;
        ";

        $stmt = $this->conn->prepare($query);

        //Clean data
          // Limpiar datos
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->state = htmlspecialchars(strip_tags($this->state));

        // Bind
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':state', $this->state);
        $stmt->bindParam(':assigned', $this->assigned);

          if ($stmt->execute()) {
            $this->id_board = $this->conn->lastInsertId();
            return true;
        }

        return false;

    }








}