<?php
/**
 * System Check Script - Final Version
 * 
 * This script performs comprehensive system checks:
 * - Loads configuration from config.php
 * - Tests database connections (User DB and Content DB)
 * - Verifies directory existence and writability
 * - Displays system configuration (BASE_URL, DOCUMENT_ROOT)
 * - Checks .env file readability
 * 
 * Output: Clean HTML table with color-coded status indicators
 */

// Suppress error display for clean HTML output
// Errors are still caught and displayed in the check results
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Load configuration
$configPath = __DIR__ . '/config/config.php';
$configLoaded = false;
$configError = '';

try {
    if (file_exists($configPath)) {
        require_once $configPath;
        $configLoaded = true;
    } else {
        $configError = "Config file not found at: $configPath";
    }
} catch (Exception $e) {
    $configError = "Error loading config: " . $e->getMessage();
}

// Initialize results array
$results = [];

// 1. Check config.php loading
$results[] = [
    'check' => 'Load config.php',
    'status' => $configLoaded,
    'message' => $configLoaded ? 'Successfully loaded from ' . $configPath : $configError,
    'details' => ''
];

// 2. Test User DB Connection
$userDbStatus = false;
$userDbMessage = '';
$userDbDetails = '';

if ($configLoaded && defined('DB_USER_HOST')) {
    try {
        // Suppress connection warnings as we handle errors via connect_error
        $userConn = @new mysqli(DB_USER_HOST, DB_USER_USER, DB_USER_PASS, DB_USER_NAME);
        
        if ($userConn->connect_error) {
            $userDbMessage = 'Connection failed';
            $userDbDetails = 'Error: ' . $userConn->connect_error;
        } else {
            $userDbStatus = true;
            $userDbMessage = 'Connected successfully';
            $userDbDetails = 'Host: ' . DB_USER_HOST . ' | Database: ' . DB_USER_NAME;
            $userConn->close();
        }
    } catch (Exception $e) {
        $userDbMessage = 'Connection error';
        $userDbDetails = 'Exception: ' . $e->getMessage();
    }
} else {
    $userDbMessage = 'Cannot test - config not loaded or constants not defined';
}

$results[] = [
    'check' => 'User Database Connection',
    'status' => $userDbStatus,
    'message' => $userDbMessage,
    'details' => $userDbDetails
];

// 3. Test Content DB Connection
$contentDbStatus = false;
$contentDbMessage = '';
$contentDbDetails = '';

if ($configLoaded && defined('DB_CONTENT_HOST')) {
    try {
        // Suppress connection warnings as we handle errors via connect_error
        $contentConn = @new mysqli(DB_CONTENT_HOST, DB_CONTENT_USER, DB_CONTENT_PASS, DB_CONTENT_NAME);
        
        if ($contentConn->connect_error) {
            $contentDbMessage = 'Connection failed';
            $contentDbDetails = 'Error: ' . $contentConn->connect_error;
        } else {
            $contentDbStatus = true;
            $contentDbMessage = 'Connected successfully';
            $contentDbDetails = 'Host: ' . DB_CONTENT_HOST . ' | Database: ' . DB_CONTENT_NAME;
            $contentConn->close();
        }
    } catch (Exception $e) {
        $contentDbMessage = 'Connection error';
        $contentDbDetails = 'Exception: ' . $e->getMessage();
    }
} else {
    $contentDbMessage = 'Cannot test - config not loaded or constants not defined';
}

$results[] = [
    'check' => 'Content Database Connection',
    'status' => $contentDbStatus,
    'message' => $contentDbMessage,
    'details' => $contentDbDetails
];

// 4. Check assets/uploads directory
$uploadsPath = __DIR__ . '/assets/uploads';
$uploadsExists = file_exists($uploadsPath) && is_dir($uploadsPath);
$uploadsWritable = $uploadsExists && is_writable($uploadsPath);
$uploadsStatus = $uploadsExists && $uploadsWritable;

$uploadsMessage = '';
$uploadsPermissions = 'N/A';
if (!$uploadsExists) {
    $uploadsMessage = 'Directory does not exist';
} elseif (!$uploadsWritable) {
    $uploadsMessage = 'Directory exists but is not writable';
    $uploadsPermissions = substr(sprintf('%o', fileperms($uploadsPath)), -4);
} else {
    $uploadsMessage = 'Directory exists and is writable';
    $uploadsPermissions = substr(sprintf('%o', fileperms($uploadsPath)), -4);
}

$results[] = [
    'check' => 'assets/uploads Directory',
    'status' => $uploadsStatus,
    'message' => $uploadsMessage,
    'details' => 'Path: ' . $uploadsPath . ' | Permissions: ' . $uploadsPermissions
];

// 5. Check BASE_URL
$baseUrlDefined = defined('BASE_URL');
$baseUrlValue = $baseUrlDefined ? BASE_URL : 'Not defined';

$results[] = [
    'check' => 'BASE_URL Configuration',
    'status' => $baseUrlDefined,
    'message' => $baseUrlDefined ? 'Defined and resolved' : 'Not defined',
    'details' => 'Value: ' . htmlspecialchars($baseUrlValue)
];

// 6. Check DOCUMENT_ROOT
$documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? 'Not available';
$documentRootExists = !empty($documentRoot) && file_exists($documentRoot);

$results[] = [
    'check' => 'DOCUMENT_ROOT',
    'status' => $documentRootExists,
    'message' => $documentRootExists ? 'Available and exists' : 'Not available or does not exist',
    'details' => 'Path: ' . htmlspecialchars($documentRoot)
];

// 7. Check .env file readability
$envPath = __DIR__ . '/.env';
$envExists = file_exists($envPath);
$envReadable = $envExists && is_readable($envPath);

$envMessage = '';
if (!$envExists) {
    $envMessage = 'File does not exist';
} elseif (!$envReadable) {
    $envMessage = 'File exists but is not readable';
} else {
    $envMessage = 'File exists and is readable';
}

$results[] = [
    'check' => '.env File',
    'status' => $envReadable,
    'message' => $envMessage,
    'details' => 'Path: ' . $envPath . ' | Size: ' . ($envExists ? filesize($envPath) . ' bytes' : 'N/A')
];

// Calculate overall status
$totalChecks = count($results);
$passedChecks = count(array_filter($results, function($result) { return $result['status']; }));
$overallStatus = $passedChecks === $totalChecks;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Check - Final Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .summary {
            padding: 20px 30px;
            background: <?php echo $overallStatus ? '#d4edda' : '#f8d7da'; ?>;
            border-bottom: 3px solid <?php echo $overallStatus ? '#28a745' : '#dc3545'; ?>;
            text-align: center;
        }
        
        .summary h2 {
            color: <?php echo $overallStatus ? '#155724' : '#721c24'; ?>;
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .summary p {
            color: <?php echo $overallStatus ? '#155724' : '#721c24'; ?>;
            font-size: 16px;
        }
        
        .results {
            padding: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 16px;
        }
        
        .status-pass {
            background: #28a745;
            color: white;
        }
        
        .status-fail {
            background: #dc3545;
            color: white;
        }
        
        .check-name {
            font-weight: 600;
            color: #212529;
            font-size: 15px;
        }
        
        .message {
            color: #495057;
            font-size: 14px;
        }
        
        .details {
            color: #6c757d;
            font-size: 13px;
            margin-top: 5px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        
        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            color: #6c757d;
            font-size: 13px;
        }
        
        .timestamp {
            font-weight: 600;
            color: #495057;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            table {
                font-size: 13px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 System Check Report</h1>
            <p>Comprehensive system validation and configuration check</p>
        </div>
        
        <div class="summary">
            <h2><?php echo $overallStatus ? '✓ All Checks Passed' : '⚠ Some Checks Failed'; ?></h2>
            <p><?php echo $passedChecks; ?> of <?php echo $totalChecks; ?> checks passed successfully</p>
        </div>
        
        <div class="results">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">Status</th>
                        <th style="width: 250px;">Check</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $result): ?>
                    <tr>
                        <td style="text-align: center;">
                            <span class="status-indicator <?php echo $result['status'] ? 'status-pass' : 'status-fail'; ?>">
                                <?php echo $result['status'] ? '✓' : '✗'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="check-name"><?php echo htmlspecialchars($result['check']); ?></div>
                        </td>
                        <td>
                            <div class="message"><?php echo htmlspecialchars($result['message']); ?></div>
                            <?php if (!empty($result['details'])): ?>
                                <div class="details"><?php echo htmlspecialchars($result['details']); ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Generated on <span class="timestamp"><?php echo date('Y-m-d H:i:s'); ?></span></p>
            <p>Script location: <?php echo htmlspecialchars(__FILE__); ?></p>
        </div>
    </div>
</body>
</html>
