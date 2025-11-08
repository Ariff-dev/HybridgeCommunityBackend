<?php

require_once __DIR__ . '/../Controllers/UserController.php';

$routes = [
    'GET:users' => [
        'controller' => 'userController',
        'action' => 'index'
    ],
    'GET:user' => [
        'controller' => 'userController',
        'action'=> 'show'
    ]
];