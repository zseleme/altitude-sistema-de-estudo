<?php
/**
 * Error Handling Helper
 * Sanitizes error messages to prevent information disclosure
 */

class ErrorHelper {
    /**
     * Get a sanitized error message for production
     * @param Exception $e The exception
     * @param bool $isDevelopment Whether in development mode
     * @return string Safe error message
     */
    public static function getSafeErrorMessage(Exception $e, $isDevelopment = false) {
        // Log the full error for debugging
        error_log("Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        error_log("Stack trace: " . $e->getTraceAsString());

        // In development, show detailed errors
        if ($isDevelopment || self::isDevelopmentEnvironment()) {
            return $e->getMessage();
        }

        // In production, return generic messages based on error type
        $message = $e->getMessage();

        // Database errors
        if (stripos($message, 'SQL') !== false ||
            stripos($message, 'database') !== false ||
            stripos($message, 'PDO') !== false ||
            stripos($message, 'query') !== false) {
            return 'Erro ao processar sua solicitação no banco de dados.';
        }

        // File system errors
        if (stripos($message, 'file') !== false ||
            stripos($message, 'directory') !== false ||
            stripos($message, 'permission') !== false) {
            return 'Erro ao acessar recursos do sistema.';
        }

        // API errors
        if (stripos($message, 'API') !== false ||
            stripos($message, 'curl') !== false ||
            stripos($message, 'connection') !== false) {
            return 'Erro ao comunicar com serviço externo.';
        }

        // Authentication/Authorization errors
        if (stripos($message, 'auth') !== false ||
            stripos($message, 'login') !== false ||
            stripos($message, 'permission') !== false) {
            return 'Erro de autenticação. Por favor, faça login novamente.';
        }

        // Default generic message
        return 'Ocorreu um erro ao processar sua solicitação.';
    }

    /**
     * Check if running in development environment
     * @return bool
     */
    private static function isDevelopmentEnvironment() {
        // Check for common development indicators
        if (getenv('APP_ENV') === 'development' ||
            getenv('APP_ENV') === 'dev' ||
            getenv('APP_DEBUG') === 'true') {
            return true;
        }

        // Check if running on localhost
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        if (in_array($serverName, ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }

        // Check for .git directory (development environment)
        if (file_exists(__DIR__ . '/../.git')) {
            return true;
        }

        return false;
    }

    /**
     * Send JSON error response
     * @param Exception $e The exception
     * @param int $httpCode HTTP status code
     * @param bool $isDevelopment Whether in development mode
     */
    public static function sendJsonError(Exception $e, $httpCode = 500, $isDevelopment = false) {
        http_response_code($httpCode);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => self::getSafeErrorMessage($e, $isDevelopment)
        ];

        // Include detailed error info in development
        if ($isDevelopment || self::isDevelopmentEnvironment()) {
            $response['debug'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Send HTML error response
     * @param Exception $e The exception
     * @param int $httpCode HTTP status code
     * @param bool $isDevelopment Whether in development mode
     */
    public static function sendHtmlError(Exception $e, $httpCode = 500, $isDevelopment = false) {
        http_response_code($httpCode);

        $safeMessage = self::getSafeErrorMessage($e, $isDevelopment);

        echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro - Altitude</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md">
        <div class="text-center">
            <div class="text-red-500 text-6xl mb-4">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Erro</h1>
            <p class="text-gray-600 mb-6">' . htmlspecialchars($safeMessage) . '</p>
            <a href="/" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Voltar ao Início
            </a>
        </div>';

        // Show debug info in development
        if ($isDevelopment || self::isDevelopmentEnvironment()) {
            echo '
        <div class="mt-8 p-4 bg-gray-100 rounded text-left">
            <h2 class="font-bold mb-2">Debug Information:</h2>
            <p class="text-sm"><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <p class="text-sm"><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . '</p>
            <p class="text-sm"><strong>Line:</strong> ' . $e->getLine() . '</p>
            <p class="text-sm mt-2"><strong>Stack Trace:</strong></p>
            <pre class="text-xs overflow-auto">' . htmlspecialchars($e->getTraceAsString()) . '</pre>
        </div>';
        }

        echo '
    </div>
</body>
</html>';
        exit;
    }
}
