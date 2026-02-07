<?php
/**
 * Production Environment Reset Script
 * 
 * Removes all development, migration, and setup clutter to prepare for a clean production start.
 * This script will delete development files, migration scripts, and test files that are no longer needed
 * after the new SQL schemas (full_user_schema.sql and full_content_schema.sql) are imported.
 * 
 * SAFETY: Requires ?confirm=yes GET parameter to execute
 * 
 * WARNING: This script will delete itself after execution!
 */

// Require confirmation parameter
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    http_response_code(403);
    echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Confirmation Required</title>\n</head>\n<body>\n";
    echo "<h1>⚠️ Confirmation Required</h1>\n";
    echo "<p>This script will <strong>permanently delete</strong> development and migration files.</p>\n";
    echo "<p>To proceed, add <code>?confirm=yes</code> to the URL.</p>\n";
    echo "</body>\n</html>";
    exit;
}

// Set execution time limit
set_time_limit(300);

// Define the blacklist of files to delete
$blacklist = [
    // Installation and system check files
    'install_fresh.php',
    'check_system_final.php',
    'check_location.php',
    
    // Deployment and migration files
    'deploy_migrations.php',
    'migrate_projects.php',
    
    // Fix scripts
    'fix_event_db.php',
    'fix_event_image.php',
    
    // Test files
    'test_mail_live.php',
    'test_event_signup_implementation.php',
    
    // Debug files
    'debug_paths.php',
    'debug_500.php',
    'debug_white_screen.php',
    
    // Verification and cleanup files
    'verify_db_schema.php',
    'post_deploy_cleanup.php',
    'cleanup_final.php',
    'cleanup_structure.php',
    'cleanup_system.php',
    'cleanup_members.php',
    
    // Old duplicate
    'pages/members/directory.php'
];

// Files to keep in sql/ directory
$sqlKeepFiles = [
    'full_user_schema.sql',
    'full_content_schema.sql'
];

// Whitelist - Files and directories that should NEVER be deleted (Invoice Module protection)
$whitelist = [
    'pages/invoices/',
    'includes/models/Invoice.php',
    'api/submit_invoice.php',
    'api/export_invoices.php',
    'src/MailService.php',
    'uploads/invoices/'
];

// Initialize results array
$results = [];

/**
 * Delete a file and record the result
 */
function deleteFile($filepath, &$results, $whitelist = []) {
    // Check if file is in whitelist - protect it from deletion
    foreach ($whitelist as $protected) {
        if ($filepath === $protected || strpos($filepath, rtrim($protected, '/')) === 0) {
            $results[] = [
                'file' => $filepath,
                'status' => 'Protected (Invoice Module)',
                'class' => 'kept'
            ];
            return false;
        }
    }
    
    $fullPath = __DIR__ . '/' . $filepath;
    
    // Validate that the path is within the working directory
    $realPath = realpath(dirname($fullPath));
    if ($realPath === false || strpos($realPath, __DIR__) !== 0) {
        $results[] = [
            'file' => $filepath,
            'status' => 'Invalid path',
            'class' => 'error'
        ];
        return false;
    }
    
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            $results[] = [
                'file' => $filepath,
                'status' => 'Deleted',
                'class' => 'deleted'
            ];
            return true;
        } else {
            $error = error_get_last();
            $results[] = [
                'file' => $filepath,
                'status' => 'Failed to delete: ' . ($error['message'] ?? 'Unknown error'),
                'class' => 'error'
            ];
            return false;
        }
    } else {
        $results[] = [
            'file' => $filepath,
            'status' => 'Not found',
            'class' => 'not-found'
        ];
        return false;
    }
}

// Delete blacklisted files
foreach ($blacklist as $file) {
    deleteFile($file, $results, $whitelist);
}

// Handle sql/ directory - delete all files except the ones to keep
$sqlDir = __DIR__ . '/sql';
if (is_dir($sqlDir)) {
    $sqlFiles = scandir($sqlDir);
    foreach ($sqlFiles as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filepath = 'sql/' . $file;
        $fullPath = $sqlDir . '/' . $file;
        
        // Skip if it's a directory
        if (is_dir($fullPath)) {
            continue;
        }
        
        // Keep the master schema files
        if (in_array($file, $sqlKeepFiles)) {
            $results[] = [
                'file' => $filepath,
                'status' => 'Kept (master schema)',
                'class' => 'kept'
            ];
            continue;
        }
        
        // Delete all other files
        if (unlink($fullPath)) {
            $results[] = [
                'file' => $filepath,
                'status' => 'Deleted',
                'class' => 'deleted'
            ];
        } else {
            $error = error_get_last();
            $results[] = [
                'file' => $filepath,
                'status' => 'Failed to delete: ' . ($error['message'] ?? 'Unknown error'),
                'class' => 'error'
            ];
        }
    }
}

// Output results as HTML table
?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Environment Reset - Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            max-width: 800px;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .deleted {
            color: #4CAF50;
            font-weight: bold;
        }
        .kept {
            color: #2196F3;
            font-weight: bold;
        }
        .not-found {
            color: #999;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .summary {
            margin: 20px 0;
            padding: 15px;
            background-color: white;
            max-width: 800px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .warning {
            margin: 20px 0;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            max-width: 800px;
        }
    </style>
</head>
<body>
    <h1>🧹 Production Environment Reset - Results</h1>
    
    <div class="summary">
        <h2>Summary</h2>
        <?php
        $deleted = count(array_filter($results, fn($r) => $r['status'] === 'Deleted'));
        $kept = count(array_filter($results, fn($r) => strpos($r['status'], 'Kept') !== false));
        $notFound = count(array_filter($results, fn($r) => $r['status'] === 'Not found'));
        $errors = count(array_filter($results, fn($r) => strpos($r['status'], 'Failed') !== false));
        ?>
        <p><strong>Deleted:</strong> <?php echo $deleted; ?> files</p>
        <p><strong>Kept:</strong> <?php echo $kept; ?> files</p>
        <p><strong>Not found:</strong> <?php echo $notFound; ?> files</p>
        <p><strong>Errors:</strong> <?php echo $errors; ?> files</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>File</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result): ?>
                <tr>
                    <td><?php echo htmlspecialchars($result['file']); ?></td>
                    <td class="<?php echo $result['class']; ?>">
                        <?php echo htmlspecialchars($result['status']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="warning">
        <h3>⚠️ Self-Destruct</h3>
        <p>This script will now delete itself...</p>
    </div>
</body>
</html>
<?php

// Ensure output is sent to browser before self-destruct
if (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// Self-destruct - delete this script
$scriptPath = __FILE__;
if (file_exists($scriptPath)) {
    unlink($scriptPath);
}
