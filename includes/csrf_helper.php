<?php
/**
 * CSRF Protection Helper
 * Provides functions to generate and validate CSRF tokens
 */

class CSRFHelper {
    private static $tokenKey = 'csrf_token';
    private static $tokenExpiry = 3600; // 1 hour

    /**
     * Generate a new CSRF token
     * @return string The generated token
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$tokenKey] = [
            'token' => $token,
            'time' => time()
        ];

        return $token;
    }

    /**
     * Get the current CSRF token (or generate if not exists)
     * @return string The CSRF token
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // If token doesn't exist or is expired, generate new one
        if (!isset($_SESSION[self::$tokenKey]) || self::isTokenExpired()) {
            return self::generateToken();
        }

        return $_SESSION[self::$tokenKey]['token'];
    }

    /**
     * Check if the current token is expired
     * @return bool
     */
    private static function isTokenExpired() {
        if (!isset($_SESSION[self::$tokenKey]['time'])) {
            return true;
        }

        return (time() - $_SESSION[self::$tokenKey]['time']) > self::$tokenExpiry;
    }

    /**
     * Validate a CSRF token
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::$tokenKey]['token'])) {
            return false;
        }

        if (self::isTokenExpired()) {
            return false;
        }

        // Use hash_equals to prevent timing attacks
        return hash_equals($_SESSION[self::$tokenKey]['token'], $token);
    }

    /**
     * Validate CSRF token from request and send error response if invalid
     * @param bool $json Whether to send JSON response (default: true)
     * @return void Exits on failure
     */
    public static function validateRequest($json = true) {
        $token = null;

        // Check for token in POST, GET, or headers
        if (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } elseif (isset($_GET['csrf_token'])) {
            $token = $_GET['csrf_token'];
        } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!$token || !self::validateToken($token)) {
            http_response_code(403);

            if ($json) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Token CSRF inválido ou expirado. Recarregue a página.'
                ]);
            } else {
                echo 'Token CSRF inválido ou expirado. Recarregue a página.';
            }

            exit;
        }
    }

    /**
     * Generate HTML hidden input field with CSRF token
     * @return string HTML input field
     */
    public static function getTokenField() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get token as meta tag for use in AJAX requests
     * @return string HTML meta tag
     */
    public static function getTokenMeta() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
