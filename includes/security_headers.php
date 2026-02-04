<?php
/**
 * Security Headers Helper
 * Sets HTTP security headers to protect against common attacks
 */

class SecurityHeaders {
    /**
     * Apply all security headers
     * @param array $options Configuration options for headers
     */
    public static function apply($options = []) {
        // Only apply headers once
        if (headers_sent()) {
            return;
        }

        // Content Security Policy (CSP)
        self::applyCSP($options['csp'] ?? []);

        // X-Frame-Options - Prevents clickjacking
        self::applyFrameOptions($options['frame_options'] ?? 'DENY');

        // X-Content-Type-Options - Prevents MIME sniffing
        header('X-Content-Type-Options: nosniff');

        // X-XSS-Protection - Basic XSS protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy - Controls referrer information
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy)
        self::applyPermissionsPolicy($options['permissions'] ?? []);

        // HTTPS enforcement (if on HTTPS)
        if (self::isHttps()) {
            // HTTP Strict Transport Security (HSTS)
            $maxAge = $options['hsts_max_age'] ?? 31536000; // 1 year
            header("Strict-Transport-Security: max-age={$maxAge}; includeSubDomains");
        }
    }

    /**
     * Apply Content Security Policy header
     * @param array $customDirectives Custom CSP directives
     */
    private static function applyCSP($customDirectives = []) {
        // Default CSP directives
        $defaultDirectives = [
            "default-src" => "'self'",
            "script-src" => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "style-src" => "'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "img-src" => "'self' data: https: http:",
            "font-src" => "'self' https://cdnjs.cloudflare.com data:",
            "connect-src" => "'self'",
            "frame-src" => "https://www.youtube.com https://player.vimeo.com https://onedrive.live.com",
            "frame-ancestors" => "'none'",
            "base-uri" => "'self'",
            "form-action" => "'self'",
            "upgrade-insecure-requests" => ""
        ];

        // Merge with custom directives
        $directives = array_merge($defaultDirectives, $customDirectives);

        // Build CSP string
        $cspParts = [];
        foreach ($directives as $directive => $value) {
            if (!empty($value)) {
                $cspParts[] = "{$directive} {$value}";
            } else {
                $cspParts[] = $directive;
            }
        }

        $csp = implode('; ', $cspParts);

        // Apply CSP header
        header("Content-Security-Policy: {$csp}");
    }

    /**
     * Apply X-Frame-Options header
     * @param string $option DENY, SAMEORIGIN, or ALLOW-FROM uri
     */
    private static function applyFrameOptions($option) {
        $validOptions = ['DENY', 'SAMEORIGIN'];

        if (in_array(strtoupper($option), $validOptions)) {
            header("X-Frame-Options: {$option}");
        } else {
            header('X-Frame-Options: DENY');
        }
    }

    /**
     * Apply Permissions Policy header
     * @param array $customPolicies Custom policies
     */
    private static function applyPermissionsPolicy($customPolicies = []) {
        // Default restrictive permissions
        $defaultPolicies = [
            'geolocation' => '()',
            'microphone' => '()',
            'camera' => '()',
            'payment' => '()',
            'usb' => '()',
            'magnetometer' => '()',
            'gyroscope' => '()',
            'accelerometer' => '()'
        ];

        // Merge with custom policies
        $policies = array_merge($defaultPolicies, $customPolicies);

        // Build policy string
        $policyParts = [];
        foreach ($policies as $feature => $allowlist) {
            $policyParts[] = "{$feature}={$allowlist}";
        }

        $policy = implode(', ', $policyParts);

        // Apply Permissions Policy header
        header("Permissions-Policy: {$policy}");
    }

    /**
     * Check if connection is over HTTPS
     * @return bool
     */
    private static function isHttps() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || $_SERVER['SERVER_PORT'] == 443
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Apply minimal security headers (for APIs)
     * @param bool $allowCors Whether to allow CORS
     */
    public static function applyMinimal($allowCors = false) {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');

        if ($allowCors) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
        }
    }

    /**
     * Apply security headers for development environment
     * Less restrictive CSP to allow hot reloading, etc.
     */
    public static function applyDevelopment() {
        if (headers_sent()) {
            return;
        }

        // More permissive CSP for development
        $devCSP = [
            "default-src" => "'self' 'unsafe-inline' 'unsafe-eval'",
            "script-src" => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "style-src" => "'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com",
            "img-src" => "'self' data: https: http:",
            "font-src" => "'self' https://cdnjs.cloudflare.com data:",
            "connect-src" => "'self' ws: wss:",
        ];

        self::applyCSP($devCSP);

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
