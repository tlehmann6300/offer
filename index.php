<?php
// Temporarily enable error display for diagnosis
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load configuration
require_once 'config/config.php';

// Load authentication class
require_once 'src/Auth.php';

// Check if user is authenticated
if (Auth::check()) {
    // User is authenticated - redirect to dashboard
    $redirectUrl = 'pages/dashboard/index.php';
    
    // Try to redirect with header
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Fallback if headers already sent
        echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weiterleitung...</title>
    <script>
        window.location.href = "' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '";
    </script>
</head>
<body>
    <p>Leite weiter... Falls nichts passiert, klicke <a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">hier</a>.</p>
</body>
</html>';
        exit;
    }
} else {
    // User is not authenticated - redirect to login
    $redirectUrl = 'pages/auth/login.php';
    
    // Try to redirect with header
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Fallback if headers already sent
        echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weiterleitung...</title>
    <script>
        window.location.href = "' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '";
    </script>
</head>
<body>
    <p>Leite weiter... Falls nichts passiert, klicke <a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">hier</a>.</p>
</body>
</html>';
        exit;
    }
}
