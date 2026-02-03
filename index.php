<?php
// Temporarily enable error display for diagnosis
// TODO: Remove these lines after debugging or use environment-based config
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once 'config/config.php';

// Load authentication class
require_once 'src/Auth.php';

/**
 * Redirect with fallback mechanisms
 * 
 * @param string $url Target URL for redirection
 */
function redirectWithFallback($url) {
    // Validate URL doesn't contain newline characters (header injection protection)
    if (strpos($url, "\n") !== false || strpos($url, "\r") !== false) {
        die('Invalid redirect URL');
    }
    
    // Try to redirect with header
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }
    
    // Fallback if headers already sent
    $safeUrlHtml = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $safeUrlJs = json_encode($url, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    
    echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weiterleitung...</title>
    <script>
        window.location.href = ' . $safeUrlJs . ';
    </script>
</head>
<body>
    <p>Leite weiter... Falls nichts passiert, klicke <a href="' . $safeUrlHtml . '">hier</a>.</p>
</body>
</html>';
    exit;
}

// Check if user is authenticated
if (Auth::check()) {
    // User is authenticated - redirect to dashboard
    redirectWithFallback('pages/dashboard/index.php');
} else {
    // User is not authenticated - redirect to login
    redirectWithFallback('pages/auth/login.php');
}
