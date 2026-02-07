<?php
/**
 * Production Database Configuration Script
 * This script configures the Content Database (Invoices) with production credentials
 * 
 * IMPORTANT: Run this script once to configure production, then DELETE it for security!
 */

// Production credentials (hardcoded as per requirements)
define('PROD_DB_CONTENT_HOST', 'db5019505323.hosting-data.io');
define('PROD_DB_CONTENT_PORT', '3306');
define('PROD_DB_CONTENT_USER', 'dbu387360');
define('PROD_DB_CONTENT_PASS', 'F9!qR7#L@2mZ$8KAS44');

$success = false;
$error = '';

// Simple token-based CSRF protection
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_content_name'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $dbContentName = trim($_POST['db_content_name']);
        
        // Server-side validation
        if (empty($dbContentName)) {
            $error = 'Database name cannot be empty.';
        } elseif (!preg_match('/^dbs[0-9]+$/', $dbContentName)) {
            $error = 'Database name must start with "dbs" followed by numbers.';
        } else {
        try {
            $envFile = __DIR__ . '/.env';
            
            // Read existing .env file
            if (!file_exists($envFile)) {
                throw new Exception('.env file not found.');
            }
            
            $lines = file($envFile, FILE_IGNORE_NEW_LINES);
            $updatedLines = [];
            $keysToUpdate = [
                'DB_CONTENT_HOST' => PROD_DB_CONTENT_HOST,
                'DB_CONTENT_PORT' => PROD_DB_CONTENT_PORT,
                'DB_CONTENT_USER' => PROD_DB_CONTENT_USER,
                'DB_CONTENT_PASS' => PROD_DB_CONTENT_PASS,
                'DB_CONTENT_NAME' => $dbContentName
            ];
            $foundKeys = [];
            $lastContentKeyPosition = -1;
            
            // Process each line
            foreach ($lines as $line) {
                $updated = false;
                
                // Check if this line contains one of our keys
                foreach ($keysToUpdate as $key => $value) {
                    if (preg_match('/^' . preg_quote($key, '/') . '\s*=/', $line)) {
                        // Replace the line with new value
                        $updatedLines[] = $key . '=' . $value;
                        $foundKeys[$key] = true;
                        $lastContentKeyPosition = count($updatedLines) - 1;
                        $updated = true;
                        break;
                    }
                }
                
                // If not updated, keep the original line
                if (!$updated) {
                    $updatedLines[] = $line;
                }
            }
            
            // Find any missing keys
            $missingKeys = [];
            foreach ($keysToUpdate as $key => $value) {
                if (!isset($foundKeys[$key])) {
                    $missingKeys[$key] = $value;
                }
            }
            
            // Insert missing keys after the last DB_CONTENT_* key or after Content Database comment
            if (count($missingKeys) > 0) {
                if ($lastContentKeyPosition >= 0) {
                    // Insert after the last DB_CONTENT_* key found
                    $before = array_slice($updatedLines, 0, $lastContentKeyPosition + 1);
                    $after = array_slice($updatedLines, $lastContentKeyPosition + 1);
                    
                    foreach ($missingKeys as $key => $value) {
                        $before[] = $key . '=' . $value;
                    }
                    
                    $updatedLines = array_merge($before, $after);
                } else {
                    // Find Content Database comment and insert after it
                    $insertPosition = -1;
                    for ($i = 0; $i < count($updatedLines); $i++) {
                        if (stripos($updatedLines[$i], 'Content Database') !== false) {
                            $insertPosition = $i + 1;
                            break;
                        }
                    }
                    
                    if ($insertPosition > 0) {
                        $before = array_slice($updatedLines, 0, $insertPosition);
                        $after = array_slice($updatedLines, $insertPosition);
                        
                        foreach ($missingKeys as $key => $value) {
                            $before[] = $key . '=' . $value;
                        }
                        
                        $updatedLines = array_merge($before, $after);
                    } else {
                        // No content section found, append at the end with a new section
                        $updatedLines[] = '';
                        $updatedLines[] = '# Content Database Configuration (Projects, Inventory, Events, News, System Logs)';
                        foreach ($missingKeys as $key => $value) {
                            $updatedLines[] = $key . '=' . $value;
                        }
                    }
                }
            }
            
            // Write back to .env file
            $content = implode(PHP_EOL, $updatedLines);
            if (file_put_contents($envFile, $content) === false) {
                throw new Exception('Failed to write to .env file.');
            }
            
            $success = true;
            
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Database Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .info-box h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .credential-list {
            list-style: none;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #555;
        }
        
        .credential-list li {
            padding: 5px 0;
        }
        
        .credential-list strong {
            color: #667eea;
            display: inline-block;
            min-width: 180px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: 'Courier New', monospace;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .hint {
            color: #888;
            font-size: 12px;
            margin-top: 5px;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-left: 4px solid #28a745;
            color: #155724;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .success-message h2 {
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .warning-box strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Production Database Setup</h1>
        <p class="subtitle">Configure Content Database for Invoice Module</p>
        
        <?php if ($success): ?>
            <div class="success-message">
                <h2>✅ Content Database configured. Now run migrations.</h2>
                <p>The .env file has been updated with production database credentials.</p>
            </div>
            
            <div class="warning-box">
                <strong>🔒 SECURITY NOTICE:</strong>
                Please delete this file (setup_production_db.php) immediately for security reasons!
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error-message">
                    <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>📋 Production Credentials</h3>
                <ul class="credential-list">
                    <li><strong>DB_CONTENT_HOST:</strong> <?php echo htmlspecialchars(PROD_DB_CONTENT_HOST); ?></li>
                    <li><strong>DB_CONTENT_PORT:</strong> <?php echo htmlspecialchars(PROD_DB_CONTENT_PORT); ?></li>
                    <li><strong>DB_CONTENT_USER:</strong> <?php echo htmlspecialchars(PROD_DB_CONTENT_USER); ?></li>
                    <li><strong>DB_CONTENT_PASS:</strong> <?php echo str_repeat('•', strlen(PROD_DB_CONTENT_PASS)); ?></li>
                </ul>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="db_content_name">Database Name (DB_CONTENT_NAME)</label>
                    <input 
                        type="text" 
                        id="db_content_name" 
                        name="db_content_name" 
                        placeholder="dbs..." 
                        required
                        pattern="dbs[0-9]+"
                        title="Database name should start with 'dbs' followed by numbers"
                    >
                    <p class="hint">Enter the database name (e.g., dbs12345678)</p>
                </div>
                
                <button type="submit">Configure Production Database</button>
            </form>
            
            <div class="warning-box">
                <strong>⚠️ Important:</strong>
                This will update your .env file with production credentials. Make sure you have a backup before proceeding.
            </div>
            
            <div class="warning-box" style="border-left-color: #dc3545; margin-top: 15px;">
                <strong>🔒 SECURITY NOTICE:</strong>
                This script contains hardcoded production credentials. Delete this file immediately after use to prevent unauthorized access!
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
