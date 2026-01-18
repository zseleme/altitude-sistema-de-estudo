<?php
/**
 * Input Validation Helper
 * Provides validation functions for user input
 */

class InputValidator {
    /**
     * Validate string length
     * @param string $value The value to validate
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @param string $fieldName Field name for error message
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateLength($value, $minLength, $maxLength, $fieldName = 'Campo') {
        $length = mb_strlen($value);

        if ($length < $minLength) {
            return [
                'valid' => false,
                'error' => "{$fieldName} deve ter no mínimo {$minLength} caracteres."
            ];
        }

        if ($length > $maxLength) {
            return [
                'valid' => false,
                'error' => "{$fieldName} deve ter no máximo {$maxLength} caracteres."
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate required field
     * @param mixed $value The value to validate
     * @param string $fieldName Field name for error message
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateRequired($value, $fieldName = 'Campo') {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (empty($value) && $value !== '0' && $value !== 0) {
            return [
                'valid' => false,
                'error' => "{$fieldName} é obrigatório."
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate email format
     * @param string $email Email to validate
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Email inválido.'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate numeric range
     * @param mixed $value The value to validate
     * @param int|float $min Minimum value
     * @param int|float $max Maximum value
     * @param string $fieldName Field name for error message
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateRange($value, $min, $max, $fieldName = 'Campo') {
        if (!is_numeric($value)) {
            return [
                'valid' => false,
                'error' => "{$fieldName} deve ser um número."
            ];
        }

        $numValue = floatval($value);

        if ($numValue < $min || $numValue > $max) {
            return [
                'valid' => false,
                'error' => "{$fieldName} deve estar entre {$min} e {$max}."
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate enum (value in allowed list)
     * @param mixed $value The value to validate
     * @param array $allowedValues Allowed values
     * @param string $fieldName Field name for error message
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateEnum($value, array $allowedValues, $fieldName = 'Campo') {
        if (!in_array($value, $allowedValues, true)) {
            return [
                'valid' => false,
                'error' => "{$fieldName} inválido."
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate URL format
     * @param string $url URL to validate
     * @param bool $requireHttps Whether to require HTTPS
     * @return array ['valid' => bool, 'error' => string]
     */
    public static function validateUrl($url, $requireHttps = false) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => 'URL inválida.'
            ];
        }

        if ($requireHttps && !str_starts_with($url, 'https://')) {
            return [
                'valid' => false,
                'error' => 'URL deve usar HTTPS.'
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate multiple fields at once
     * @param array $validations Array of validation rules
     * @return array ['valid' => bool, 'errors' => array]
     *
     * Example:
     * $validations = [
     *     ['method' => 'validateRequired', 'args' => [$title, 'Título']],
     *     ['method' => 'validateLength', 'args' => [$title, 3, 255, 'Título']],
     * ];
     */
    public static function validateMultiple(array $validations) {
        $errors = [];
        $allValid = true;

        foreach ($validations as $validation) {
            $method = $validation['method'] ?? null;
            $args = $validation['args'] ?? [];

            if (!$method || !method_exists(self::class, $method)) {
                continue;
            }

            $result = call_user_func_array([self::class, $method], $args);

            if (!$result['valid']) {
                $allValid = false;
                $errors[] = $result['error'];
            }
        }

        return [
            'valid' => $allValid,
            'errors' => $errors
        ];
    }

    /**
     * Sanitize string input (strip tags, trim)
     * @param string $value Input value
     * @param bool $allowHtml Whether to allow HTML tags
     * @return string Sanitized value
     */
    public static function sanitize($value, $allowHtml = false) {
        $value = trim($value);

        if (!$allowHtml) {
            $value = strip_tags($value);
        }

        return $value;
    }

    /**
     * Validate and sanitize simulado data
     * @param array $data Simulado data
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function validateSimuladoData($data) {
        $titulo = self::sanitize($data['titulo'] ?? '');
        $descricao = self::sanitize($data['descricao'] ?? '');
        $disciplina = self::sanitize($data['disciplina'] ?? '');
        $tempoLimite = intval($data['tempo_limite'] ?? 0);

        $validations = [
            ['method' => 'validateRequired', 'args' => [$titulo, 'Título']],
            ['method' => 'validateLength', 'args' => [$titulo, 3, 255, 'Título']],
            ['method' => 'validateLength', 'args' => [$descricao, 0, 1000, 'Descrição']],
            ['method' => 'validateRange', 'args' => [$tempoLimite, 0, 600, 'Tempo limite']],
        ];

        $result = self::validateMultiple($validations);

        return [
            'valid' => $result['valid'],
            'errors' => $result['errors'],
            'data' => [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'disciplina' => $disciplina,
                'tempo_limite' => $tempoLimite
            ]
        ];
    }

    /**
     * Validate AI configuration data
     * @param array $data Configuration data
     * @return array ['valid' => bool, 'errors' => array, 'data' => array]
     */
    public static function validateAIConfigData($data) {
        $errors = [];

        // Validate provider
        $provider = $data['ai_provider'] ?? '';
        $providerValidation = self::validateEnum($provider, ['openai', 'gemini', 'groq'], 'Provedor de IA');

        if (!$providerValidation['valid']) {
            $errors[] = $providerValidation['error'];
        }

        // Validate temperature
        $temperature = floatval($data['ai_temperature'] ?? 0.3);
        $tempValidation = self::validateRange($temperature, 0, 1, 'Temperatura');

        if (!$tempValidation['valid']) {
            $errors[] = $tempValidation['error'];
        }

        // Validate max tokens
        $maxTokens = intval($data['ai_max_tokens'] ?? 4000);
        $tokensValidation = self::validateRange($maxTokens, 100, 16000, 'Máximo de tokens');

        if (!$tokensValidation['valid']) {
            $errors[] = $tokensValidation['error'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $data
        ];
    }
}
