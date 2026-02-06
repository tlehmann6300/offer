<?php
/**
 * Master Maintenance Script - fix_system_status.php
 * 
 * This script performs a full system integrity check and repair:
 * 1. File Cleanup (Aggressive): Delete redundant directory.php
 * 2. Sidebar Link Fix: Update main_layout.php links
 * 3. Database Check (Critical): Verify alumni_profiles has study_program column
 * 4. Profile Logic Check: Verify profile.php contains study_program field
 */

// Suppress error display for clean HTML output
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Load configuration and database
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

// Initialize results
$results = [];

// ============================================================================
// 1. FILE CLEANUP (AGGRESSIVE)
// ============================================================================
$directoryPhpPath = __DIR__ . '/pages/members/directory.php';
$fileCleanupStatus = false;
$fileCleanupMessage = '';

if (file_exists($directoryPhpPath)) {
    // File exists, attempt to delete
    if (@unlink($directoryPhpPath)) {
        $fileCleanupStatus = true;
        $fileCleanupMessage = '<span style="color:green">✅ Deleted redundant directory.php</span>';
    } else {
        $fileCleanupStatus = false;
        $fileCleanupMessage = '<span style="color:red">❌ Failed to delete directory.php (check permissions)</span>';
    }
} else {
    // File doesn't exist (already cleaned)
    $fileCleanupStatus = true;
    $fileCleanupMessage = '<span style="color:gray">ℹ️ directory.php is already gone.</span>';
}

$results[] = [
    'check' => 'File Cleanup',
    'status' => $fileCleanupStatus,
    'message' => $fileCleanupMessage
];

// ============================================================================
// 2. SIDEBAR LINK FIX
// ============================================================================
$mainLayoutPath = __DIR__ . '/includes/templates/main_layout.php';
$sidebarFixStatus = false;
$sidebarFixMessage = '';

if (file_exists($mainLayoutPath)) {
    $mainLayoutContent = file_get_contents($mainLayoutPath);
    
    if ($mainLayoutContent !== false) {
        // Search for references to directory.php
        if (strpos($mainLayoutContent, 'pages/members/directory.php') !== false) {
            // Replace directory.php with index.php
            $updatedContent = str_replace('pages/members/directory.php', 'pages/members/index.php', $mainLayoutContent);
            
            // Write back to file
            if (file_put_contents($mainLayoutPath, $updatedContent) !== false) {
                $sidebarFixStatus = true;
                $sidebarFixMessage = '<span style="color:green">✅ Fixed Sidebar Link (directory -> index).</span>';
            } else {
                $sidebarFixStatus = false;
                $sidebarFixMessage = '<span style="color:red">❌ Failed to update main_layout.php (check permissions)</span>';
            }
        } else {
            // No reference found, already correct
            $sidebarFixStatus = true;
            $sidebarFixMessage = '<span style="color:green">✅ Sidebar links are already correct (no directory.php references).</span>';
        }
    } else {
        $sidebarFixStatus = false;
        $sidebarFixMessage = '<span style="color:red">❌ Failed to read main_layout.php</span>';
    }
} else {
    $sidebarFixStatus = false;
    $sidebarFixMessage = '<span style="color:red">❌ main_layout.php not found</span>';
}

$results[] = [
    'check' => 'Sidebar Link Fix',
    'status' => $sidebarFixStatus,
    'message' => $sidebarFixMessage
];

// ============================================================================
// 3. DATABASE CHECK (CRITICAL)
// ============================================================================
$databaseCheckStatus = false;
$databaseCheckMessage = '';

try {
    $db = Database::getContentDB();
    
    // Check if alumni_profiles table exists
    $stmt = $db->query("SHOW TABLES LIKE 'alumni_profiles'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        // Check if study_program column exists
        $stmt = $db->query("SHOW COLUMNS FROM alumni_profiles LIKE 'study_program'");
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            $databaseCheckStatus = true;
            $databaseCheckMessage = '<span style="color:green">✅ Database columns are correct.</span>';
        } else {
            // Column is missing - show critical warning
            $databaseCheckStatus = false;
            $databaseCheckMessage = '<h2 style="color:red">❌ ACTION REQUIRED: Run /sql/migrate_add_student_fields.php immediately!</h2>';
        }
    } else {
        $databaseCheckStatus = false;
        $databaseCheckMessage = '<span style="color:red">❌ Table alumni_profiles does not exist</span>';
    }
    
} catch (PDOException $e) {
    $databaseCheckStatus = false;
    $databaseCheckMessage = '<span style="color:red">❌ Database connection error: ' . htmlspecialchars($e->getMessage()) . '</span>';
} catch (Exception $e) {
    $databaseCheckStatus = false;
    $databaseCheckMessage = '<span style="color:red">❌ Database connection error: ' . htmlspecialchars($e->getMessage()) . '</span>';
}

$results[] = [
    'check' => 'Database Check (Critical)',
    'status' => $databaseCheckStatus,
    'message' => $databaseCheckMessage
];

// ============================================================================
// 4. PROFILE LOGIC CHECK
// ============================================================================
$profileLogicStatus = false;
$profileLogicMessage = '';

$profilePhpPath = __DIR__ . '/pages/auth/profile.php';

if (file_exists($profilePhpPath)) {
    $profileContent = file_get_contents($profilePhpPath);
    
    if ($profileContent !== false) {
        // Check if study_program is referenced in the file
        if (strpos($profileContent, 'study_program') !== false) {
            $profileLogicStatus = true;
            $profileLogicMessage = '<span style="color:green">✅ Profile logic contains study_program field (editable).</span>';
        } else {
            $profileLogicStatus = false;
            $profileLogicMessage = '<span style="color:red">❌ Profile logic missing study_program field (not editable).</span>';
        }
    } else {
        $profileLogicStatus = false;
        $profileLogicMessage = '<span style="color:red">❌ Failed to read profile.php</span>';
    }
} else {
    $profileLogicStatus = false;
    $profileLogicMessage = '<span style="color:red">❌ profile.php not found</span>';
}

$results[] = [
    'check' => 'Profile Logic Check',
    'status' => $profileLogicStatus,
    'message' => $profileLogicMessage
];

// ============================================================================
// Calculate Overall Status
// ============================================================================
$totalChecks = count($results);
$passedChecks = count(array_filter($results, function($result) { return $result['status']; }));
$overallStatus = $passedChecks === $totalChecks;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Integrity Check & Repair</title>
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
        
        .check-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #6c757d;
        }
        
        .check-item.pass {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .check-item.fail {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .check-item h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #212529;
        }
        
        .check-item .message {
            font-size: 15px;
            line-height: 1.6;
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
            
            .results {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 System Integrity Check & Repair</h1>
            <p>Master maintenance script for full system integrity verification</p>
        </div>
        
        <div class="summary">
            <h2><?php echo $overallStatus ? '✓ All Checks Passed' : '⚠ Some Issues Found'; ?></h2>
            <p><?php echo $passedChecks; ?> of <?php echo $totalChecks; ?> checks passed successfully</p>
        </div>
        
        <div class="results">
            <?php foreach ($results as $result): ?>
            <div class="check-item <?php echo $result['status'] ? 'pass' : 'fail'; ?>">
                <h3><?php echo htmlspecialchars($result['check']); ?></h3>
                <div class="message"><?php echo $result['message']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>Generated on <span class="timestamp"><?php echo date('Y-m-d H:i:s'); ?></span></p>
            <p>Script location: <?php echo htmlspecialchars(__FILE__); ?></p>
        </div>
    </div>
</body>
</html>
