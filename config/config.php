<?php
/**
 * Database Configuration
 * Two separate databases for security and structure
 */

// User Database (Authentication, Logins, Passwords, Alumni Profiles)
define('DB_USER_HOST', 'db5019508945.hosting-data.io');
define('DB_USER_NAME', 'dbs15253086');
define('DB_USER_USER', 'dbu4494103');
define('DB_USER_PASS', 'Q9!mZ7$A2v#Lr@8x');

// Content Database (Projects, Inventory, Events, News, System Logs)
define('DB_CONTENT_HOST', 'db5019375140.hosting-data.io');
define('DB_CONTENT_NAME', 'dbs15161271');
define('DB_CONTENT_USER', 'dbu2067984');
define('DB_CONTENT_PASS', 'Wort!Zahl?Wort#41254g');

// SMTP Configuration
define('SMTP_HOST', 'smtp.ionos.de');
define('SMTP_PORT', 587);
define('SMTP_USER', 'mail@test.business-consulting.de');
define('SMTP_PASS', 'Test12345678.');
define('SMTP_FROM', 'mail@test.business-consulting.de');

// Application Settings
define('APP_NAME', 'IBC Intranet');
define('BASE_URL', ''); // Set this to your domain
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

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');
