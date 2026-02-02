<?php
/**
 * Auth Class
 * Wrapper/Alias for includes/handlers/AuthHandler.php
 * Provides compatibility for new src/ structure
 */

require_once __DIR__ . '/../includes/handlers/AuthHandler.php';

// Create an alias so code can use either 'Auth' or 'AuthHandler'
if (class_exists('AuthHandler')) {
    class_alias('AuthHandler', 'Auth');
}
