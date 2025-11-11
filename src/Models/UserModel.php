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

}