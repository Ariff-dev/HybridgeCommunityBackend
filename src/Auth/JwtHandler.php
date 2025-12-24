<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler
{
    private $secretKey;
    private $accessTokenExpiry;
    private $refreshTokenExpiry;
    private $algorithm = 'HS256';

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'default-secret-change-in-production';
        $this->accessTokenExpiry = (int) ($_ENV['JWT_ACCESS_TOKEN_EXPIRY'] ?? 900); // 15 minutes
        $this->refreshTokenExpiry = (int) ($_ENV['JWT_REFRESH_TOKEN_EXPIRY'] ?? 604800); // 7 days
    }

    /**
     * Generate access token (short-lived)
     * 
     * @param string $userId User ID
     * @param string $email User email
     * @return string JWT token
     */
    public function generateAccessToken($userId, $email)
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->accessTokenExpiry;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'data' => [
                'user_id' => $userId,
                'email' => $email
            ]
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Generate refresh token (random string, not JWT)
     * 
     * @return string Random token
     */
    public function generateRefreshToken()
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Validate and decode JWT token
     * 
     * @param string $token JWT token
     * @return object|false Decoded token or false if invalid
     */
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
        } catch (Exception $e) {
            error_log('JWT Validation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user data from token
     * 
     * @param string $token JWT token
     * @return array|false User data or false if invalid
     */
    public function getTokenPayload($token)
    {
        $decoded = $this->validateToken($token);

        if (!$decoded) {
            return false;
        }

        return [
            'user_id' => $decoded->data->user_id ?? null,
            'email' => $decoded->data->email ?? null,
            'exp' => $decoded->exp ?? null
        ];
    }

    /**
     * Get refresh token expiry timestamp
     * 
     * @return int Timestamp
     */
    public function getRefreshTokenExpiry()
    {
        return time() + $this->refreshTokenExpiry;
    }

    /**
     * Extract token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getBearerToken()
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
