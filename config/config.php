<?php
ob_start();

// Manually parse .env file
$envFile = __DIR__ . '/../.env';
$env = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
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
            
            // Remove quotes if present (and value is at least 2 chars for quotes)
            if (strlen($value) >= 2) {
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            
            $env[$key] = $value;
        }
    }
}

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

// Generic Database Constants (from .env if available, defaults to user DB)
define('DB_HOST', $env['DB_HOST'] ?? $env['DB_USER_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? $env['DB_USER_NAME'] ?? '');
define('DB_USER', $env['DB_USER'] ?? $env['DB_USER_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? $env['DB_USER_PASS'] ?? '');

// SMTP Configuration
define('SMTP_HOST', $env['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', $env['SMTP_PORT'] ?? 587);
define('SMTP_USER', $env['SMTP_USER'] ?? '');
define('SMTP_PASS', $env['SMTP_PASS'] ?? '');
define('SMTP_FROM', $env['SMTP_FROM'] ?? $env['SMTP_USER'] ?? '');
define('SMTP_FROM_EMAIL', $env['SMTP_FROM_EMAIL'] ?? $env['SMTP_FROM'] ?? $env['SMTP_USER'] ?? '');
define('SMTP_FROM_NAME', $env['SMTP_FROM_NAME'] ?? 'IBC Intranet');

// Define BASE_URL dynamically if not in .env
if (isset($env['BASE_URL'])) {
    define('BASE_URL', $env['BASE_URL']);
} else {
    $protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . '://' . $host . '/intra');
}

// Application Settings
define('APP_NAME', 'IBC Intranet');
define('ENVIRONMENT', $env['ENVIRONMENT'] ?? 'development');
define('SESSION_LIFETIME', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);
define('UPLOAD_MAX_SIZE', 5242880);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Security
define('HASH_ALGO', PASSWORD_ARGON2ID);
define('SESSION_NAME', 'IBC_SESSION');

// Set error display for production (0) or debugging (1)
$isProduction = ($env['ENVIRONMENT'] ?? '') === 'production';
ini_set('display_errors', $isProduction ? '0' : '1');
error_reporting($isProduction ? 0 : E_ALL);

// Set timezone
date_default_timezone_set('Europe/Berlin');

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load helper functions globally
require_once __DIR__ . '/../includes/helpers.php';