<?php

header('Content-Type: application/json; charset=utf-8;');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Origin: Content-Type, Authorization');

//?Preflight
if ( $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get method and route
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Route in parts
$uriParts = explode('/', $uri);

try {

    $endpoint = $uriParts[0];
    
    if ( $endpoint == 'api') {
        array_shift($uriParts);
        $endpoint = $uriParts[0] ?? '';
    }

    require_once __DIR__ . '/src/Routes/api.php';

    // handleRoute

} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error'=> true,
        'message'=> $_ENV['API_DEBUG'] === 'true' ? $e->getMessage(): 'Error interno del servidor'
    ]);
}


function handleRoute($method, $endpoint, $uriParts){
    global $routes;

    $routeKey = 'method:$enpoint';

    if ( isset($routes[$routeKey]) ){
        $route = $routes[$routeKey];

        // Controller instance
        
    }
}

