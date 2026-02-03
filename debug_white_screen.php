<?php
/**
 * Debug White Screen Script
 * Diagnostic tool to identify issues causing white screen errors
 * Tests configuration, database connections, and critical directories
 */

// Set error reporting immediately (before anything else)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize results array
$results = [];
$allOk = true;

// Header
echo "<!DOCTYPE html>\n";
echo "<html lang='de'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "    <title>System Debug</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }\n";
echo "        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }\n";
echo "        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }\n";
echo "        .check { margin: 15px 0; padding: 10px; border-left: 4px solid #28a745; background: #d4edda; }\n";
echo "        .error { margin: 15px 0; padding: 10px; border-left: 4px solid #dc3545; background: #f8d7da; }\n";
echo "        .status-ok { color: #28a745; font-weight: bold; font-size: 1.5em; margin-top: 20px; }\n";
echo "        .status-error { color: #dc3545; font-weight: bold; font-size: 1.5em; margin-top: 20px; }\n";
echo "        pre { background: #f4f4f4; padding: 10px; border-radius: 3px; overflow-x: auto; }\n";
echo "        .info { color: #666; font-size: 0.9em; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";
echo "<div class='container'>\n";
echo "    <h1>🔍 System Debug - White Screen Diagnostic</h1>\n";

// Test 1: Check PHP version
echo "    <div class='check'>\n";
echo "        <strong>✓ PHP Version:</strong> " . PHP_VERSION . "\n";
echo "    </div>\n";
$results[] = "PHP Version: " . PHP_VERSION;

// Test 2: Try to include config/config.php
echo "    <h2>Configuration File</h2>\n";
try {
    $configPath = __DIR__ . '/config/config.php';
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found at: $configPath");
    }
    
    require_once $configPath;
    echo "    <div class='check'>\n";
    echo "        <strong>✓ Config loaded successfully</strong><br>\n";
    echo "        <span class='info'>Path: $configPath</span>\n";
    echo "    </div>\n";
    $results[] = "Config: OK";
} catch (Exception $e) {
    echo "    <div class='error'>\n";
    echo "        <strong>✗ Config Error:</strong><br>\n";
    echo "        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    echo "    </div>\n";
    $results[] = "Config: FAILED - " . $e->getMessage();
    $allOk = false;
}

// Test 3: Try to establish database connection
echo "    <h2>Database Connections</h2>\n";

// Test User Database
try {
    if (!defined('DB_USER_HOST')) {
        throw new Exception("Database constants not defined. Config may not have loaded correctly.");
    }
    
    $userDbConnection = new PDO(
        "mysql:host=" . DB_USER_HOST . ";dbname=" . DB_USER_NAME . ";charset=utf8mb4",
        DB_USER_USER,
        DB_USER_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "    <div class='check'>\n";
    echo "        <strong>✓ User Database Connection:</strong> OK<br>\n";
    echo "        <span class='info'>Host: " . DB_USER_HOST . " | Database: " . DB_USER_NAME . "</span>\n";
    echo "    </div>\n";
    $results[] = "User DB: OK";
    $userDbConnection = null; // Close connection
} catch (PDOException $e) {
    echo "    <div class='error'>\n";
    echo "        <strong>✗ User Database Connection Error:</strong><br>\n";
    echo "        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    if (defined('DB_USER_HOST')) {
        echo "        <span class='info'>Host: " . DB_USER_HOST . " | Database: " . DB_USER_NAME . "</span>\n";
    }
    echo "    </div>\n";
    $results[] = "User DB: FAILED - " . $e->getMessage();
    $allOk = false;
} catch (Exception $e) {
    echo "    <div class='error'>\n";
    echo "        <strong>✗ User Database Error:</strong><br>\n";
    echo "        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    echo "    </div>\n";
    $results[] = "User DB: FAILED - " . $e->getMessage();
    $allOk = false;
}

// Test Content Database
try {
    if (!defined('DB_CONTENT_HOST')) {
        throw new Exception("Content database constants not defined. Config may not have loaded correctly.");
    }
    
    $contentDbConnection = new PDO(
        "mysql:host=" . DB_CONTENT_HOST . ";dbname=" . DB_CONTENT_NAME . ";charset=utf8mb4",
        DB_CONTENT_USER,
        DB_CONTENT_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "    <div class='check'>\n";
    echo "        <strong>✓ Content Database Connection:</strong> OK<br>\n";
    echo "        <span class='info'>Host: " . DB_CONTENT_HOST . " | Database: " . DB_CONTENT_NAME . "</span>\n";
    echo "    </div>\n";
    $results[] = "Content DB: OK";
    $contentDbConnection = null; // Close connection
} catch (PDOException $e) {
    echo "    <div class='error'>\n";
    echo "        <strong>✗ Content Database Connection Error:</strong><br>\n";
    echo "        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    if (defined('DB_CONTENT_HOST')) {
        echo "        <span class='info'>Host: " . DB_CONTENT_HOST . " | Database: " . DB_CONTENT_NAME . "</span>\n";
    }
    echo "    </div>\n";
    $results[] = "Content DB: FAILED - " . $e->getMessage();
    $allOk = false;
} catch (Exception $e) {
    echo "    <div class='error'>\n";
    echo "        <strong>✗ Content Database Error:</strong><br>\n";
    echo "        <pre>" . htmlspecialchars($e->getMessage()) . "</pre>\n";
    echo "    </div>\n";
    $results[] = "Content DB: FAILED - " . $e->getMessage();
    $allOk = false;
}

// Test 4: Check important directories
echo "    <h2>Directory Checks</h2>\n";

$directories = [
    'uploads/',
    'uploads/events/',
    'config/',
    'includes/',
    'assets/',
    'templates/'
];

foreach ($directories as $dir) {
    $dirPath = __DIR__ . '/' . $dir;
    if (file_exists($dirPath) && is_dir($dirPath)) {
        $writable = is_writable($dirPath);
        echo "    <div class='check'>\n";
        echo "        <strong>✓ Directory exists:</strong> $dir<br>\n";
        echo "        <span class='info'>Writable: " . ($writable ? 'Yes' : 'No') . " | Path: $dirPath</span>\n";
        echo "    </div>\n";
        $results[] = "Directory $dir: OK" . ($writable ? " (writable)" : " (read-only)");
    } else {
        echo "    <div class='error'>\n";
        echo "        <strong>✗ Directory missing:</strong> $dir<br>\n";
        echo "        <span class='info'>Expected path: $dirPath</span>\n";
        echo "    </div>\n";
        $results[] = "Directory $dir: MISSING";
        $allOk = false;
    }
}

// Test 5: Check .env file
echo "    <h2>Environment Configuration</h2>\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "    <div class='check'>\n";
    echo "        <strong>✓ .env file exists</strong><br>\n";
    echo "        <span class='info'>Path: $envPath</span>\n";
    echo "    </div>\n";
    $results[] = ".env: OK";
} else {
    echo "    <div class='error'>\n";
    echo "        <strong>✗ .env file missing</strong><br>\n";
    echo "        <span class='info'>Expected path: $envPath</span>\n";
    echo "    </div>\n";
    $results[] = ".env: MISSING";
    $allOk = false;
}

// Final Status
echo "    <hr>\n";
if ($allOk) {
    echo "    <div class='status-ok'>✓ System Status: OK</div>\n";
    echo "    <p>All checks passed successfully. The system appears to be configured correctly.</p>\n";
} else {
    echo "    <div class='status-error'>✗ System Status: ERRORS DETECTED</div>\n";
    echo "    <p>One or more checks failed. Please review the errors above and fix the issues.</p>\n";
}

// Summary
echo "    <h2>Summary</h2>\n";
echo "    <pre>\n";
foreach ($results as $result) {
    echo htmlspecialchars($result) . "\n";
}
echo "    </pre>\n";

echo "</div>\n";
echo "</body>\n";
echo "</html>\n";
