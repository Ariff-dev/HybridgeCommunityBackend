<?php

require_once __DIR__ . '/../Controllers/UserController.php';
require_once __DIR__ . '/../Controllers/BoardController.php';
require_once __DIR__ . '/../Controllers/AuthController.php';
require_once __DIR__ . '/../Controllers/BlogPostController.php';
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
    ],

    // Blog Post routes
    'GET:blog/posts' => [
        'controller' => 'BlogPostController',
        'action' => 'index',
        'auth' => false  // Public route - can list posts without auth
    ],
    'GET:blog/posts/{id}' => [
        'controller' => 'BlogPostController',
        'action' => 'show',
        'auth' => false  // Public route - can view single post without auth
    ],
    'POST:blog/posts' => [
        'controller' => 'BlogPostController',
        'action' => 'store',
        'auth' => true  // Protected - requires authentication to create
    ],
    'PUT:blog/posts/{id}' => [
        'controller' => 'BlogPostController',
        'action' => 'update',
        'auth' => true  // Protected - requires authentication to update
    ],
    'DELETE:blog/posts/{id}' => [
        'controller' => 'BlogPostController',
        'action' => 'delete',
        'auth' => true  // Protected - requires authentication to delete
    ],
    'POST:blog/posts/{id}/publish' => [
        'controller' => 'BlogPostController',
        'action' => 'publish',
        'auth' => true  // Protected - requires authentication to publish
    ],
    'POST:blog/posts/{id}/like' => [
        'controller' => 'BlogPostController',
        'action' => 'toggleLike',
        'auth' => true  // Protected - requires authentication to like
    ]
];