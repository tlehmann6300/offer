<?php
/**
 * Web-accessible test for security headers
 * Access this via browser or curl to verify headers
 */

require_once __DIR__ . '/config/config.php';

// Get headers
$headers = headers_list();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Security Headers Test</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .header-list { background: #f5f5f5; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Security Headers Test</h1>
    
    <h2>Headers Being Sent:</h2>
    <div class="header-list">
        <?php
        if (empty($headers)) {
            echo "<p class='error'>No headers captured (may be normal in some environments)</p>";
            echo "<p>Use browser developer tools or curl -I to see actual headers</p>";
        } else {
            foreach ($headers as $header) {
                echo htmlspecialchars($header) . "<br>";
            }
        }
        ?>
    </div>
    
    <h2>Expected Security Headers:</h2>
    <ul>
        <li>X-Content-Type-Options: nosniff</li>
        <li>X-Frame-Options: SAMEORIGIN</li>
        <li>X-XSS-Protection: 1; mode=block</li>
        <li>Referrer-Policy: strict-origin-when-cross-origin</li>
        <li>Content-Security-Policy: [CSP rules]</li>
    </ul>
    
    <h2>How to Verify:</h2>
    <p>Run from command line:</p>
    <code>curl -I <?php echo htmlspecialchars((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?></code>
    
    <p>Or check browser developer tools → Network tab → Select this request → Headers</p>
</body>
</html>
