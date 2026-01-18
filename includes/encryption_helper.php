<?php
/**
 * Encryption helper for sensitive data (API keys, etc.)
 * Uses AES-256-CBC encryption
 */

class EncryptionHelper {
    private static $method = 'AES-256-CBC';

    /**
     * Get encryption key from environment or generate a secure default
     * IMPORTANT: Set ENCRYPTION_KEY in your environment for production
     */
    private static function getEncryptionKey() {
        // Try to get from environment first
        $key = getenv('ENCRYPTION_KEY');

        // If not set, use a key derived from database config path
        // This is not ideal but better than storing in plaintext
        if (empty($key)) {
            $configPath = __DIR__ . '/../config/database.php';
            if (file_exists($configPath)) {
                $key = hash('sha256', filemtime($configPath) . filesize($configPath) . $configPath);
            } else {
                // Fallback - generate from server unique identifier
                $key = hash('sha256', php_uname() . __DIR__);
            }
        }

        return substr(hash('sha256', $key), 0, 32);
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
