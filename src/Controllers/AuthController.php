<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Auth/JwtHandler.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/RefreshTokenModel.php';

class AuthController {
    private $db;
    private $conn;
    private $userModel;
    private $refreshTokenModel;
    private $jwtHandler;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->userModel = new UserModel($this->conn);
        $this->refreshTokenModel = new RefreshTokenModel($this->conn);
        $this->jwtHandler = new JwtHandler();
    }

    /**
     * Register a new user
     * POST /api/auth/register
     */
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate input
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Name, email, and password are required'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Invalid email format'
            ]);
            return;
        }

        // Validate password length
        if (strlen($data['password']) < 6) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Password must be at least 6 characters'
            ]);
            return;
        }

        try {
            // Check if email already exists
            $existingUser = $this->userModel->getUserByEmail($data['email']);
            if ($existingUser) {
                http_response_code(409);
                echo json_encode([
                    'error' => true,
                    'message' => 'Email already registered'
                ]);
                return;
            }

            // Create user
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password']
            ];

            $userId = $this->userModel->createUser($userData);

            if ($userId) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'User registered successfully',
                    'data' => [
                        'user_id' => $userId,
                        'email' => $data['email'],
                        'name' => $data['name']
                    ]
                ]);
            } else {
                throw new Exception('Failed to create user');
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Registration failed'
            ]);
        }
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate input
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Email and password are required'
            ]);
            return;
        }

        try {
            // Get user by email
            $user = $this->userModel->getUserByEmail($data['email']);

            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'error' => true,
                    'message' => 'Invalid credentials'
                ]);
                return;
            }

            // Verify password
            if (!password_verify($data['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode([
                    'error' => true,
                    'message' => 'Invalid credentials'
                ]);
                return;
            }

            // Generate tokens
            $accessToken = $this->jwtHandler->generateAccessToken($user['id_user'], $user['email']);
            $refreshToken = $this->jwtHandler->generateRefreshToken();
            $refreshExpiry = $this->jwtHandler->getRefreshTokenExpiry();

            // Store refresh token
            $this->refreshTokenModel->create($user['id_user'], $refreshToken, $refreshExpiry);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => (int)$_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 900,
                    'user' => [
                        'id' => $user['id_user'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ]
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Login failed'
            ]);
        }
    }

    /**
     * Refresh access token
     * POST /api/auth/refresh
     */
    public function refresh() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['refresh_token'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Refresh token is required'
            ]);
            return;
        }

        try {
            // Validate refresh token
            $tokenData = $this->refreshTokenModel->validate($data['refresh_token']);

            if (!$tokenData) {
                http_response_code(401);
                echo json_encode([
                    'error' => true,
                    'message' => 'Invalid or expired refresh token'
                ]);
                return;
            }

            // Get user
            $user = $this->userModel->getUserById($tokenData['user_id']);

            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'error' => true,
                    'message' => 'User not found'
                ]);
                return;
            }

            // Generate new access token
            $accessToken = $this->jwtHandler->generateAccessToken($user['id_user'], $user['email']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => (int)$_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 900
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Token refresh failed'
            ]);
        }
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    public function logout() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['refresh_token'])) {
            http_response_code(400);
            echo json_encode([
                'error' => true,
                'message' => 'Refresh token is required'
            ]);
            return;
        }

        try {
            // Revoke refresh token
            $this->refreshTokenModel->revoke($data['refresh_token']);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Logout failed'
            ]);
        }
    }

    /**
     * Get current authenticated user
     * GET /api/auth/me
     */
    public function me() {
        // Get user from request context (set by middleware)
        global $currentUser;

        if (!$currentUser) {
            http_response_code(401);
            echo json_encode([
                'error' => true,
                'message' => 'Unauthorized'
            ]);
            return;
        }

        try {
            $user = $this->userModel->getUserById($currentUser['user_id']);

            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'error' => true,
                    'message' => 'User not found'
                ]);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $user['id_user'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'created_at' => $user['created_at']
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => $_ENV['API_DEBUG'] === 'true' ? $e->getMessage() : 'Failed to get user data'
            ]);
        }
    }
}
