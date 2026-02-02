<?php
/**
 * Configuration File
 * Loads settings from .env file
 * Two separate databases for security and structure
 */

// Load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove inline comments (everything after # that's not in quotes)
            if (strpos($value, '#') !== false) {
                // Simple handling: if # exists and not quoted, remove it
                if (!((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                      (substr($value, 0, 1) === "'" && substr($value, -1) === "'"))) {
                    $value = trim(explode('#', $value)[0]);
                }
            }
            
            // Remove quotes if present (and value is longer than 2 chars)
            if (strlen($value) > 2) {
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            
            $env[$key] = $value;
        }
    }
    
    return $env;
}

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
$env = loadEnv($envFile);

// User Database (Authentication, Logins, Passwords, Alumni Profiles)
define('DB_USER_HOST', $env['DB_USER_HOST'] ?? 'localhost');
define('DB_USER_NAME', $env['DB_USER_NAME'] ?? '');
define('DB_USER_USER', $env['DB_USER_USER'] ?? '');
define('DB_USER_PASS', $env['DB_USER_PASS'] ?? '');

// Content Database (Projects, Inventory, Events, News, System Logs)
define('DB_CONTENT_HOST', $env['DB_CONTENT_HOST'] ?? 'localhost');
define('DB_CONTENT_NAME', $env['DB_CONTENT_NAME'] ?? '');
define('DB_CONTENT_USER', $env['DB_CONTENT_USER'] ?? '');
define('DB_CONTENT_PASS', $env['DB_CONTENT_PASS'] ?? '');

// SMTP Configuration
define('SMTP_HOST', $env['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', $env['SMTP_PORT'] ?? 587);
define('SMTP_USER', $env['SMTP_USER'] ?? '');
define('SMTP_PASS', $env['SMTP_PASS'] ?? '');
define('SMTP_FROM', $env['SMTP_FROM'] ?? $env['SMTP_USER'] ?? '');

// Application Settings
define('APP_NAME', 'IBC Intranet');
define('BASE_URL', $env['BASE_URL'] ?? '');
define('SESSION_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Security
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('SESSION_NAME', 'IBC_SESSION');

// Timezone
date_default_timezone_set('Europe/Berlin');

// Error Reporting - DISABLE in production!
// Set to 0 and false for production deployment
$isProduction = ($env['ENVIRONMENT'] ?? '') === 'production';
error_reporting($isProduction ? 0 : E_ALL);
ini_set('display_errors', $isProduction ? '0' : '1');

