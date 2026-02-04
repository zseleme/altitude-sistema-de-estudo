<?php
/**
 * Rate Limiter Helper
 * Prevents abuse of expensive operations (AI API calls, etc.)
 */

class RateLimiter {
    private $db;

    // Rate limit configurations
    const AI_LIMIT_PER_HOUR = 30;      // 30 AI requests per hour per user
    const AI_LIMIT_PER_MINUTE = 5;      // 5 AI requests per minute per user
    const GENERAL_LIMIT_PER_MINUTE = 60; // 60 requests per minute for general APIs

    public function __construct() {
        require_once __DIR__ . '/../config/database.php';
        $this->db = Database::getInstance();
    }

    /**
     * Check if user has exceeded rate limit for AI operations
     * @param int $userId User ID
     * @param string $operation Operation type (e.g., 'ai_analysis', 'ai_revision')
     * @return array ['allowed' => bool, 'retry_after' => int seconds]
     */
    public function checkAIRateLimit($userId, $operation = 'ai_general') {
        // Check minute limit
        $minuteLimit = $this->checkLimit($userId, $operation, 60, self::AI_LIMIT_PER_MINUTE);
        if (!$minuteLimit['allowed']) {
            return $minuteLimit;
        }

        // Check hourly limit
        $hourLimit = $this->checkLimit($userId, $operation, 3600, self::AI_LIMIT_PER_HOUR);
        if (!$hourLimit['allowed']) {
            return $hourLimit;
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    /**
     * Check if user has exceeded rate limit for general operations
     * @param int $userId User ID
     * @param string $operation Operation type
     * @return array ['allowed' => bool, 'retry_after' => int seconds]
     */
    public function checkGeneralRateLimit($userId, $operation) {
        return $this->checkLimit($userId, $operation, 60, self::GENERAL_LIMIT_PER_MINUTE);
    }

    /**
     * Generic rate limit checker
     * @param int $userId User ID
     * @param string $operation Operation identifier
     * @param int $windowSeconds Time window in seconds
     * @param int $maxRequests Maximum requests allowed in window
     * @return array ['allowed' => bool, 'retry_after' => int seconds]
     */
    private function checkLimit($userId, $operation, $windowSeconds, $maxRequests) {
        $key = "ratelimit_{$userId}_{$operation}_{$windowSeconds}";
        $now = time();

        // Try to get from session first (faster)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Clean old entries
        $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $windowSeconds) {
            return ($now - $timestamp) < $windowSeconds;
        });

        // Check if limit exceeded
        $requestCount = count($_SESSION[$key]);

        if ($requestCount >= $maxRequests) {
            // Calculate retry_after
            $oldestRequest = min($_SESSION[$key]);
            $retryAfter = max(1, $windowSeconds - ($now - $oldestRequest));

            return [
                'allowed' => false,
                'retry_after' => $retryAfter,
                'limit' => $maxRequests,
                'window' => $windowSeconds
            ];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }

    /**
     * Record a request (call this after checkLimit returns allowed=true)
     * @param int $userId User ID
     * @param string $operation Operation type
     * @param int $windowSeconds Time window
     */
    public function recordRequest($userId, $operation, $windowSeconds = 3600) {
        $key = "ratelimit_{$userId}_{$operation}_{$windowSeconds}";
        $now = time();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        $_SESSION[$key][] = $now;

        // Also record in database for persistent tracking (optional)
        try {
            $this->recordInDatabase($userId, $operation);
        } catch (Exception $e) {
            error_log("Failed to record rate limit in database: " . $e->getMessage());
        }
    }

    /**
     * Record request in database (for analytics and persistent tracking)
     */
    private function recordInDatabase($userId, $operation) {
        // Create table if not exists
        $this->ensureTableExists();

        $query = "INSERT INTO rate_limit_log (user_id, operation, timestamp) VALUES (?, ?, ?)";
        $this->db->execute($query, [$userId, $operation, date('Y-m-d H:i:s')]);
    }

    /**
     * Ensure rate limit log table exists
     */
    private function ensureTableExists() {
        static $tableCreated = false;

        if ($tableCreated) {
            return;
        }

        try {
            // Create table
            $this->db->execute("
                CREATE TABLE IF NOT EXISTS rate_limit_log (
                    id INTEGER PRIMARY KEY " . ($this->db->isPostgreSQL() ? "SERIAL" : "AUTOINCREMENT") . ",
                    user_id INTEGER NOT NULL,
                    operation VARCHAR(100) NOT NULL,
                    timestamp " . ($this->db->isSQLite() ? "DATETIME" : "TIMESTAMP") . " DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create index separately (SQLite doesn't support inline INDEX in CREATE TABLE)
            $this->db->execute("
                CREATE INDEX IF NOT EXISTS idx_user_operation
                ON rate_limit_log(user_id, operation, timestamp)
            ");

            $tableCreated = true;
        } catch (Exception $e) {
            error_log("Failed to create rate_limit_log table: " . $e->getMessage());
        }
    }

    /**
     * Send rate limit exceeded response
     * @param array $limitInfo Result from checkLimit
     * @param bool $json Whether to send JSON response
     */
    public static function sendRateLimitResponse($limitInfo, $json = true) {
        http_response_code(429); // Too Many Requests
        header('Retry-After: ' . $limitInfo['retry_after']);

        $message = sprintf(
            'Limite de requisições excedido. Você pode fazer %d requisições a cada %d segundos. Tente novamente em %d segundos.',
            $limitInfo['limit'] ?? 0,
            $limitInfo['window'] ?? 0,
            $limitInfo['retry_after'] ?? 0
        );

        if ($json) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $message,
                'retry_after' => $limitInfo['retry_after'],
                'limit' => $limitInfo['limit'] ?? 0,
                'window' => $limitInfo['window'] ?? 0
            ]);
        } else {
            echo $message;
        }
        exit;
    }

    /**
     * Get current usage statistics for a user
     * @param int $userId
     * @param string $operation
     * @return array Usage stats
     */
    public function getUsageStats($userId, $operation = 'ai_general') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stats = [
            'last_hour' => 0,
            'last_minute' => 0,
            'remaining_hour' => self::AI_LIMIT_PER_HOUR,
            'remaining_minute' => self::AI_LIMIT_PER_MINUTE
        ];

        $now = time();

        // Check hour window
        $keyHour = "ratelimit_{$userId}_{$operation}_3600";
        if (isset($_SESSION[$keyHour])) {
            $recentHour = array_filter($_SESSION[$keyHour], function($timestamp) use ($now) {
                return ($now - $timestamp) < 3600;
            });
            $stats['last_hour'] = count($recentHour);
            $stats['remaining_hour'] = max(0, self::AI_LIMIT_PER_HOUR - $stats['last_hour']);
        }

        // Check minute window
        $keyMinute = "ratelimit_{$userId}_{$operation}_60";
        if (isset($_SESSION[$keyMinute])) {
            $recentMinute = array_filter($_SESSION[$keyMinute], function($timestamp) use ($now) {
                return ($now - $timestamp) < 60;
            });
            $stats['last_minute'] = count($recentMinute);
            $stats['remaining_minute'] = max(0, self::AI_LIMIT_PER_MINUTE - $stats['last_minute']);
        }

        return $stats;
    }
}
