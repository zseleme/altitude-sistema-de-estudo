<?php
/**
 * Encryption helper for sensitive data (API keys, etc.)
 * Uses AES-256-CBC encryption
 */

class EncryptionHelper {
    private static $method = 'AES-256-CBC';

    /**
     * Get encryption key from secure sources
     * CRITICAL: This key MUST be set in production for security
     */
    private static function getEncryptionKey() {
        // Priority 1: Environment variable (most secure for production)
        $key = getenv('ENCRYPTION_KEY');

        // Priority 2: Key file in config directory (fallback for shared hosting)
        if (empty($key)) {
            $keyFile = __DIR__ . '/../config/encryption.key';
            if (file_exists($keyFile)) {
                $key = trim(file_get_contents($keyFile));
            }
        }

        // If no key is configured, throw exception
        // NEVER fall back to predictable key generation
        if (empty($key)) {
            // In development, auto-generate a key file
            if (self::isDevelopment()) {
                $key = self::generateAndSaveKey();
            } else {
                throw new Exception(
                    'CRITICAL SECURITY ERROR: Encryption key not configured. ' .
                    'Please set ENCRYPTION_KEY environment variable or create config/encryption.key file. ' .
                    'See documentation for secure key generation: bin2hex(random_bytes(32))'
                );
            }
        }

        return substr(hash('sha256', $key), 0, 32);
    }

    /**
     * Check if we're in development mode
     * @return bool
     */
    private static function isDevelopment() {
        // Check if .git directory exists (indicates local development)
        if (file_exists(__DIR__ . '/../.git')) {
            return true;
        }

        // Check for development environment indicators
        if (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'dev') {
            return true;
        }

        return false;
    }

    /**
     * Generate and save a secure encryption key (development only)
     * @return string The generated key
     */
    private static function generateAndSaveKey() {
        $keyFile = __DIR__ . '/../config/encryption.key';

        // Generate a cryptographically secure random key
        $key = bin2hex(random_bytes(32));

        // Save to file with restricted permissions
        file_put_contents($keyFile, $key);
        @chmod($keyFile, 0600); // Read/write for owner only

        error_log('AUTO-GENERATED encryption key saved to config/encryption.key');
        error_log('WARNING: For production, use ENCRYPTION_KEY environment variable instead');

        return $key;
    }

    /**
     * Generate a new secure encryption key (for manual setup)
     * This is a utility method for administrators
     * @return string A new secure key
     */
    public static function generateSecureKey() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Encrypt a value
     * @param string $value The value to encrypt
     * @return string Encrypted value with IV prepended
     */
    public static function encrypt($value) {
        if (empty($value)) {
            return '';
        }

        $key = self::getEncryptionKey();
        $ivLength = openssl_cipher_iv_length(self::$method);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($value, self::$method, $key, 0, $iv);

        // Prepend IV to encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a value
     * @param string $encrypted The encrypted value (with IV prepended)
     * @return string Decrypted value
     */
    public static function decrypt($encrypted) {
        if (empty($encrypted)) {
            return '';
        }

        $key = self::getEncryptionKey();
        $data = base64_decode($encrypted);

        $ivLength = openssl_cipher_iv_length(self::$method);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        return openssl_decrypt($encrypted, self::$method, $key, 0, $iv);
    }

    /**
     * Check if a value is encrypted (basic check)
     * @param string $value
     * @return bool
     */
    public static function isEncrypted($value) {
        if (empty($value)) {
            return false;
        }

        // Encrypted values are base64 encoded and have minimum length
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        $ivLength = openssl_cipher_iv_length(self::$method);
        return strlen($decoded) > $ivLength;
    }

    /**
     * Encrypt if not already encrypted
     * @param string $value
     * @return string
     */
    public static function encryptIfNeeded($value) {
        if (empty($value)) {
            return '';
        }

        // Don't encrypt placeholder values
        $placeholders = ['sua-chave-openai-aqui', 'sua-chave-gemini-aqui', 'sua-chave-groq-aqui'];
        if (in_array($value, $placeholders)) {
            return $value;
        }

        // If already encrypted, return as is
        if (self::isEncrypted($value)) {
            return $value;
        }

        return self::encrypt($value);
    }
}
