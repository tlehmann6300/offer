<?php
/**
 * Deployment Cleanup Script
 * Removes temporary files for production hardening
 */

// Configuration
$targetList = [
    'install_fresh.php',
    'check_system_final.php',
    'check_location.php',
    'deploy_migrations.php',
    'migrate_projects.php',
    'apply_migration.php',
    'apply_alumni_profiles_migration.php',
    'apply_event_registrations_migration.php',
    'apply_easyverein_migration.php',
    'fix_event_db.php',
    'fix_event_image.php',
    'test_mail_live.php',
    'test_event_signup_implementation.php',
    'debug_paths.php',
    'debug_500.php',
    'debug_white_screen.php',
    'verify_db_schema.php',
    'post_deploy_cleanup.php'
];

// Generate CSRF token for security
session_start();
if (!isset($_SESSION['cleanup_token'])) {
    $_SESSION['cleanup_token'] = bin2hex(random_bytes(32));
}

// Verify CSRF token and confirmation
$confirmationReceived = false;
if (isset($_POST['execute_cleanup']) && $_POST['execute_cleanup'] === 'confirmed') {
    if (isset($_POST['csrf_token']) && hash_equals($_SESSION['cleanup_token'], $_POST['csrf_token'])) {
        $confirmationReceived = true;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Deployment Cleanup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .wrapper {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: #2d3748;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.8;
            font-size: 14px;
        }
        .content {
            padding: 40px;
        }
        .confirmation-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .confirmation-box h2 {
            color: #856404;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .confirmation-box p {
            color: #856404;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn-primary {
            background: #dc3545;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #2d3748;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:hover td {
            background: #f7fafc;
        }
        .status-deleted {
            color: #28a745;
            font-weight: 600;
        }
        .status-missing {
            color: #6c757d;
            font-style: italic;
        }
        .status-error {
            color: #dc3545;
            font-weight: 600;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #e8f5e9;
            border-radius: 8px;
            border-left: 4px solid #28a745;
        }
        .summary h3 {
            color: #1b5e20;
            margin-bottom: 10px;
        }
        .warning {
            background: #f8d7da;
            border: 2px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>🔒 System Deployment Cleanup</h1>
            <p>Production Hardening Utility</p>
        </div>
        <div class="content">
<?php if (!$confirmationReceived): ?>
            <div class="confirmation-box">
                <h2>⚠️ Confirmation Required</h2>
                <p>
                    This operation will permanently remove <?php echo count($targetList); ?> temporary files 
                    from the server. This includes installation scripts, migration tools, and debug utilities.
                </p>
                <p>
                    <strong>This action cannot be undone.</strong> Please ensure you have backups if needed.
                </p>
                <form method="post">
                    <input type="hidden" name="execute_cleanup" value="confirmed">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['cleanup_token']); ?>">
                    <button type="submit" class="btn-primary">Yes, clean up system now</button>
                </form>
            </div>
            <h3 style="margin-bottom: 15px;">Files that will be removed:</h3>
            <ul style="line-height: 2; color: #4a5568;">
<?php foreach ($targetList as $item): ?>
                <li><?php echo htmlspecialchars($item); ?></li>
<?php endforeach; ?>
            </ul>
<?php else: ?>
            <h2 style="margin-bottom: 20px;">Cleanup Results</h2>
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
<?php
    $results = [];
    $successCount = 0;
    $notFoundCount = 0;
    $errorCount = 0;

    foreach ($targetList as $filename) {
        $fullPath = __DIR__ . '/' . $filename;
        $statusClass = '';
        $statusText = '';
        
        if (!file_exists($fullPath)) {
            $statusClass = 'status-missing';
            $statusText = 'Not Found';
            $notFoundCount++;
        } else {
            // Verify path doesn't contain directory traversal
            if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
                $statusClass = 'status-error';
                $statusText = 'Invalid filename';
                $errorCount++;
            } elseif (unlink($fullPath)) {
                $statusClass = 'status-deleted';
                $statusText = 'Deleted';
                $successCount++;
            } else {
                $statusClass = 'status-error';
                $statusText = 'Error: Permission denied or file locked';
                $errorCount++;
            }
        }
        
        $results[] = ['file' => $filename, 'class' => $statusClass, 'text' => $statusText];
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($filename) . "</td>";
        echo "<td class='" . $statusClass . "'>" . htmlspecialchars($statusText) . "</td>";
        echo "</tr>\n";
    }
?>
                </tbody>
            </table>
            
            <div class="summary">
                <h3>Summary</h3>
                <p>
                    Deleted: <strong><?php echo $successCount; ?></strong> | 
                    Not Found: <strong><?php echo $notFoundCount; ?></strong> | 
                    Errors: <strong><?php echo $errorCount; ?></strong>
                </p>
            </div>
            
<?php
    // Verification phase
    $remainingFiles = [];
    foreach ($targetList as $filename) {
        if (file_exists(__DIR__ . '/' . $filename)) {
            $remainingFiles[] = $filename;
        }
    }
    
    if (count($remainingFiles) === 0) {
        echo "<div class='warning'>";
        echo "<h3>🗑️ Self-Destruct Initiated</h3>";
        echo "<p>All target files have been removed. This script will now delete itself.</p>";
        echo "</div>";
        
        // Self-destruct
        $scriptPath = __FILE__;
        if (!unlink($scriptPath)) {
            error_log("Failed to self-destruct cleanup_deployment.php - manual deletion required");
        }
    } else {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Cannot Self-Destruct</h3>";
        echo "<p>Some files remain on the system. Self-destruct aborted for safety.</p>";
        echo "<ul>";
        foreach ($remainingFiles as $remaining) {
            echo "<li>" . htmlspecialchars($remaining) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
?>
<?php endif; ?>
        </div>
    </div>
</body>
</html>
