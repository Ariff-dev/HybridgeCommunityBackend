<?php

require_once __DIR__ . '/../Auth/JwtHandler.php';

class JwtAuth {
    private $jwtHandler;

    public function __construct() {
        $this->jwtHandler = new JwtHandler();
    }

    /**
     * Authenticate request using JWT
     * 
     * @return array|false User data or false if unauthorized
     */
    public function authenticate() {
        $token = JwtHandler::getBearerToken();

        if (!$token) {
            $this->sendError(401, 'Authorization token is required');
            return false;
        }

        $payload = $this->jwtHandler->getTokenPayload($token);

        if (!$payload || !isset($payload['user_id'])) {
            $this->sendError(401, 'Invalid or expired token');
            return false;
        }

        return $payload;
    }

    /**
     * Send error response
     * 
     * @param int $code HTTP status code
     * @param string $message Error message
     */
    private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'error' => true,
            'message' => $message
        ]);
        exit();
    }
}

/**
 * Helper function to require JWT authentication
 * 
 * @return array User data from token
 */
function requireJwtAuth() {
    $auth = new JwtAuth();
    $userData = $auth->authenticate();
    
    if (!$userData) {
        exit(); // Error already sent
    }
    
    // Set global variable for controllers to access
    global $currentUser;
    $currentUser = $userData;
    
    return $userData;
}
