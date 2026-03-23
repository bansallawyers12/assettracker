<?php

$viteDevHttp = '';
$viteDevWs   = '';
if (in_array(env('APP_ENV'), ['local', 'testing'], true)) {
    $viteDevHttp = ' http://127.0.0.1:5173 http://localhost:5173 http://[::1]:5173';
    $viteDevWs   = ' ws://127.0.0.1:5173 ws://localhost:5173 ws://[::1]:5173';
}

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | application to ensure sensitive data is properly protected.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Encryption Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for various encryption features in the application.
    |
    */

    'encryption' => [
        'cipher' => 'AES-256-GCM',
        'key' => env('ENCRYPTION_KEY', env('APP_KEY')),
        'previous_keys' => array_filter(explode(',', env('ENCRYPTION_PREVIOUS_KEYS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Encryption
    |--------------------------------------------------------------------------
    |
    | Configuration for database field encryption.
    |
    */

    'database' => [
        'encrypt_fields' => [
            'users' => ['email', 'phone', 'address'],
            'bank_accounts' => ['account_number', 'routing_number', 'swift_code'],
            'business_entities' => ['tax_id', 'registration_number'],
            'persons' => ['ssn', 'passport_number', 'drivers_license'],
            'invoices' => ['notes'],
            'transactions' => ['description', 'reference'],
        ],
        'encryption_key' => env('DB_ENCRYPTION_KEY', env('APP_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Encryption
    |--------------------------------------------------------------------------
    |
    | Configuration for encrypted file storage.
    |
    */

    'files' => [
        'encrypt_uploads' => true,
        'encryption_disk' => 'encrypted',
        'max_file_size' => env('MAX_FILE_SIZE', 10485760), // 10MB
        'allowed_types' => explode(',', env('ALLOWED_FILE_TYPES', 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx')),
        'scan_uploads' => true, // Scan for malware
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    |
    | Configuration for password requirements and security.
    |
    */

    'passwords' => [
        'min_length' => env('PASSWORD_MIN_LENGTH', 12),
        'require_special_chars' => env('PASSWORD_REQUIRE_SPECIAL_CHARS', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'max_age_days' => 90, // Force password change every 90 days
        'history_count' => 5, // Remember last 5 passwords
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Configuration for secure session handling.
    |
    */

    'sessions' => [
        'encrypt' => true,
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => env('SESSION_HTTP_ONLY', true),
        'same_site' => env('SESSION_SAME_SITE', 'strict'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'regenerate_on_login' => true,
        'regenerate_on_logout' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting various operations.
    |
    */

    'rate_limiting' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        'login_attempts' => env('RATE_LIMIT_ATTEMPTS', 5),
        'login_decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 15),
        'api_requests_per_minute' => 60,
        'password_reset_attempts' => 3,
        'password_reset_decay_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configuration for 2FA implementation.
    |
    */

    'two_factor' => [
        'enabled' => true,
        'issuer' => env('TWO_FA_ISSUER', env('APP_NAME', 'Asset Tracker')),
        'algorithm' => 'sha1',
        'digits' => 6,
        'period' => 30,
        'window' => 1,
        'backup_codes_count' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configuration for security headers.
    |
    */

    'headers' => [
        'enabled' => env('SECURE_HEADERS', true),
        // Never redirect to HTTPS on PHP's built-in server (`php artisan serve`) — it has no TLS.
        'force_https' => filter_var(env('FORCE_HTTPS', true), FILTER_VALIDATE_BOOLEAN)
            && PHP_SAPI !== 'cli-server',
        'hsts_max_age' => 31536000, // 1 year
        'hsts_include_subdomains' => true,
        // Google Maps / Places: script-src + connect-src for *.googleapis.com
        // @see https://developers.google.com/maps/documentation/javascript/content-security-policy
        //
        // Also allow: jsDelivr + code.jquery.com (Summernote, QR on 2FA setup), Office Online embed
        // (resources/js/documents.js), and https: on connect-src / frame-src so presigned S3 URLs work
        // across regions (standard host wildcards do not match bucket.s3.region.amazonaws.com).
        'content_security_policy' => env(
            'CONTENT_SECURITY_POLICY',
            "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'{$viteDevHttp} https://cdn.jsdelivr.net https://code.jquery.com https://*.googleapis.com https://*.gstatic.com *.google.com https://*.ggpht.com *.googleusercontent.com blob:; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdn.jsdelivr.net{$viteDevHttp}; img-src 'self' data: https:; font-src 'self' data: https://fonts.bunny.net https://fonts.gstatic.com https://cdn.jsdelivr.net; connect-src 'self'{$viteDevHttp}{$viteDevWs} https://*.googleapis.com *.google.com https://*.gstatic.com https: data: blob:; frame-src 'self' https://view.officeapps.live.com *.google.com https:; worker-src blob:; frame-ancestors 'none';"
        ),
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Security
    |--------------------------------------------------------------------------
    |
    | Configuration for encrypted backups.
    |
    */

    'backup' => [
        'encrypt' => true,
        'encryption_key' => env('BACKUP_ENCRYPTION_KEY', env('APP_KEY')),
        'disk' => env('BACKUP_DISK', 'encrypted'),
        'retention_days' => 30,
        'compress' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for security audit logging.
    |
    */

    'audit' => [
        'enabled' => true,
        'log_failed_logins' => true,
        'log_password_changes' => true,
        'log_sensitive_operations' => true,
        'retention_days' => 365,
    ],
];
