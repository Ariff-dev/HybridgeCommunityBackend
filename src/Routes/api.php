<?php

require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/BoardController.php';
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Middlewares/JwtAuth.php';

$routes = [
    // Authentication routes (public)
    'POST:auth/register' => [
        'controller' => 'AuthController',
        'action' => 'register',
        'auth' => false
    ],
    'POST:auth/login' => [
        'controller' => 'AuthController',
        'action' => 'login',
        'auth' => false
    ],
    'POST:auth/refresh' => [
        'controller' => 'AuthController',
        'action' => 'refresh',
        'auth' => false
    ],
    'POST:auth/logout' => [
        'controller' => 'AuthController',
        'action' => 'logout',
        'auth' => true
    ],
    'GET:auth/me' => [
        'controller' => 'AuthController',
        'action' => 'me',
        'auth' => true
    ],
    
    // User routes (protected)
    'GET:users' => [
        'controller' => 'UserController',
        'action' => 'getAll',
        'auth' => true
    ],
    'GET:users/{id}' => [
        'controller' => 'UserController',
        'action' => 'getUserByEmail',
        'auth' => true
    ],
    
    // Board routes (protected)
    'GET:board' => [
        'controller' => 'BoardController',
        'action' => 'index',
        'auth' => true
    ],
    'POST:board' => [
        'controller' => 'BoardController',
        'action' => 'store',
        'auth' => true
    ],
    'PUT:board' => [
        'controller' => 'BoardController',
        'action' => 'update',
        'auth' => true
    ]
];