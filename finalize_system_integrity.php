<?php
/**
 * System Integrity Finalization Script
 * Checks and reports on system integrity issues
 * 
 * Usage: Navigate to https://your-domain.de/finalize_system_integrity.php
 */

require_once __DIR__ . '/includes/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Integrity Check</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #00a651;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 System Integrity Check</h1>
        <p>Running integrity checks on the system...</p>

        <?php
        // Track overall status
        $hasErrors = false;

        // ========================================
        // 1. FILE CLEANUP CHECK
        // ========================================
        echo "<h2>1️⃣ File Cleanup</h2>";
        
        $directoryPhpPath = __DIR__ . '/pages/members/directory.php';
        
        if (file_exists($directoryPhpPath)) {
            if (unlink($directoryPhpPath)) {
                echo "<div class='status success'>✅ Deleted obsolete directory.php</div>";
            } else {
                echo "<div class='status error'>❌ Failed to delete directory.php - check file permissions</div>";
                $hasErrors = true;
            }
        } else {
            echo "<div class='status info'>ℹ️ directory.php does not exist (already clean)</div>";
        }

        // ========================================
        // 2. SIDEBAR LINK CHECK
        // ========================================
        echo "<h2>2️⃣ Sidebar Link Check</h2>";
        
        $mainLayoutPath = __DIR__ . '/includes/templates/main_layout.php';
        
        if (file_exists($mainLayoutPath)) {
            $mainLayoutContent = file_get_contents($mainLayoutPath);
            
            if (strpos($mainLayoutContent, 'pages/members/index.php') !== false) {
                echo "<div class='status success'>✅ Sidebar link is correct</div>";
            } else {
                echo "<div class='status error'>❌ Sidebar link is wrong! Please manually update main_layout.php to point to /pages/members/index.php</div>";
                $hasErrors = true;
            }
        } else {
            echo "<div class='status error'>❌ main_layout.php not found at expected location</div>";
            $hasErrors = true;
        }

        // ========================================
        // 3. DATABASE INTEGRITY CHECK
        // ========================================
        echo "<h2>3️⃣ Database Integrity</h2>";
        
        try {
            $db = Database::getContentDB();
            
            // Check if alumni_profiles table has study_program column
            $stmt = $db->query("SHOW COLUMNS FROM alumni_profiles LIKE 'study_program'");
            $column = $stmt->fetch();
            
            if ($column) {
                echo "<div class='status success'>✅ Database is ready for students.</div>";
            } else {
                echo "<div class='status error'>❌ MISSING COLUMNS! You MUST run /sql/migrate_add_student_fields.php immediately!</div>";
                $hasErrors = true;
            }
            
        } catch (Exception $e) {
            echo "<div class='status error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            $hasErrors = true;
        }

        // ========================================
        // SUMMARY
        // ========================================
        echo "<h2>📊 Summary</h2>";
        
        if ($hasErrors) {
            echo "<div class='status error'>⚠️ System integrity check found issues that need attention. Please review the errors above.</div>";
        } else {
            echo "<div class='status success'>✅ All integrity checks passed! System is in good shape.</div>";
        }
        ?>

        <hr style="margin: 30px 0;">
        <p style="color: #666; font-size: 0.9em;">
            <strong>Script:</strong> finalize_system_integrity.php<br>
            <strong>Run at:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>
