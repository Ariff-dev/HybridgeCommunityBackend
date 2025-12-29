<?php

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// CORS Configuration
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');  // Allow all origins (development mode)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

    if ($endpoint == 'api') {
        array_shift($uriParts);
        $endpoint = implode('/', $uriParts);
    }

    require_once __DIR__ . '/../src/Routes/api.php';

    // Handle route
    handleRoute($method, $endpoint);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Error interno del servidor'
    ]);
}


function handleRoute($method, $endpoint)
{
    global $routes;

    $routeKey = "{$method}:{$endpoint}";

    if (isset($routes[$routeKey])) {
        $route = $routes[$routeKey];

        // Check if authentication is required
        if (isset($route['auth']) && $route['auth'] === true) {
            requireJwtAuth();
        }

        // Get controller and action
        $controllerName = $route['controller'];
        $action = $route['action'];

        // Instantiate controller
        $controller = new $controllerName();

        // Call action
        if (method_exists($controller, $action)) {
            $controller->$action();
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => 'Action not found'
            ]);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'Route not found'
        ]);
    }
}
