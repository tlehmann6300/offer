<?php
/**
 * Drop and Clean Production Databases
 * 
 * ⚠️ WARNING: THIS IS A DESTRUCTIVE OPERATION ⚠️
 * 
 * This script will:
 * 1. Drop all tables in the User Database (dbs15253086)
 * 2. Drop all tables in the Content Database (dbs15161271)
 * 3. Clean the server by removing uploaded files and temporary data
 * 
 * USE WITH EXTREME CAUTION!
 * Only run this if you want to completely reset the production environment.
 */

require_once __DIR__ . '/../includes/database.php';

// Set execution time limit
set_time_limit(300);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// SAFETY CHECK - REQUIRE CONFIRMATION
// ========================================

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════╗\n";
echo "║                         ⚠️  WARNING  ⚠️                           ║\n";
echo "║                                                                   ║\n";
echo "║  This script will DROP ALL TABLES in both production databases:  ║\n";
echo "║  - User Database (dbs15253086)                                   ║\n";
echo "║  - Content Database (dbs15161271)                                ║\n";
echo "║                                                                   ║\n";
echo "║  ALL DATA WILL BE PERMANENTLY DELETED!                           ║\n";
echo "║                                                                   ║\n";
echo "║  This action CANNOT be undone!                                   ║\n";
echo "╚═══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check if running from command line
if (php_sapi_name() === 'cli') {
    echo "Type 'DROP ALL DATABASES' to confirm and proceed: ";
    $confirmation = trim(fgets(STDIN));
    
    if ($confirmation !== 'DROP ALL DATABASES') {
        echo "\n❌ Confirmation failed. Aborting.\n\n";
        exit(1);
    }
} else {
    // If running from web, require a POST parameter
    if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'DROP ALL DATABASES') {
        echo "<!DOCTYPE html>\n";
        echo "<html><head><title>Drop Databases - Confirmation Required</title></head><body>\n";
        echo "<h1 style='color: red;'>⚠️ WARNING ⚠️</h1>\n";
        echo "<p style='font-size: 18px; font-weight: bold;'>\n";
        echo "This will DROP ALL TABLES in both production databases!<br>\n";
        echo "ALL DATA WILL BE PERMANENTLY DELETED!<br><br>\n";
        echo "Type 'DROP ALL DATABASES' to confirm:\n";
        echo "</p>\n";
        echo "<form method='POST'>\n";
        echo "<input type='text' name='confirm' size='30' required>\n";
        echo "<button type='submit' style='background: red; color: white; padding: 10px 20px; font-weight: bold;'>DROP ALL DATABASES</button>\n";
        echo "</form>\n";
        echo "</body></html>\n";
        exit;
    }
}

echo "\n✓ Confirmation received. Proceeding with database drop...\n\n";

$errors = [];
$success = [];

try {
    // ========================================
    // STEP 1: Drop User Database Tables
    // ========================================
    
    echo "STEP 1: Dropping all tables in User Database (dbs15253086)\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $userDB = Database::getUserDB();
    
    // Disable foreign key checks
    $userDB->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all tables in user database
    $stmt = $userDB->query("SHOW TABLES");
    $userTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($userTables) . " table(s) in User Database:\n";
    foreach ($userTables as $table) {
        echo "  • {$table}\n";
    }
    echo "\nDropping tables...\n";
    
    $droppedUserTables = 0;
    foreach ($userTables as $table) {
        try {
            $userDB->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "  ✓ Dropped table: {$table}\n";
            $droppedUserTables++;
        } catch (PDOException $e) {
            $errors[] = "Failed to drop user table {$table}: " . $e->getMessage();
            echo "  ✗ Failed to drop: {$table}\n";
        }
    }
    
    // Re-enable foreign key checks
    $userDB->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $success[] = "Dropped {$droppedUserTables} table(s) from User Database";
    echo "\n✅ User Database cleaned\n\n";
    
} catch (Exception $e) {
    $errors[] = "User DB drop failed: " . $e->getMessage();
    echo "\n❌ User Database drop failed: " . $e->getMessage() . "\n\n";
}

try {
    // ========================================
    // STEP 2: Drop Content Database Tables
    // ========================================
    
    echo "STEP 2: Dropping all tables in Content Database (dbs15161271)\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $contentDB = Database::getContentDB();
    
    // Disable foreign key checks
    $contentDB->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all tables in content database
    $stmt = $contentDB->query("SHOW TABLES");
    $contentTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($contentTables) . " table(s) in Content Database:\n";
    foreach ($contentTables as $table) {
        echo "  • {$table}\n";
    }
    echo "\nDropping tables...\n";
    
    $droppedContentTables = 0;
    foreach ($contentTables as $table) {
        try {
            $contentDB->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "  ✓ Dropped table: {$table}\n";
            $droppedContentTables++;
        } catch (PDOException $e) {
            $errors[] = "Failed to drop content table {$table}: " . $e->getMessage();
            echo "  ✗ Failed to drop: {$table}\n";
        }
    }
    
    // Re-enable foreign key checks
    $contentDB->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    $success[] = "Dropped {$droppedContentTables} table(s) from Content Database";
    echo "\n✅ Content Database cleaned\n\n";
    
} catch (Exception $e) {
    $errors[] = "Content DB drop failed: " . $e->getMessage();
    echo "\n❌ Content Database drop failed: " . $e->getMessage() . "\n\n";
}

try {
    // ========================================
    // STEP 3: Clean Server Files
    // ========================================
    
    echo "STEP 3: Cleaning server files and uploads\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $uploadDirs = [
        __DIR__ . '/../uploads/invoices',
        __DIR__ . '/../uploads/profiles',
        __DIR__ . '/../uploads/projects',
        __DIR__ . '/../uploads/inventory',
        __DIR__ . '/../uploads/events'
    ];
    
    $filesDeleted = 0;
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            echo "Cleaning directory: {$dir}\n";
            
            // Get all files in directory
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $filesDeleted++;
                        echo "  ✓ Deleted: " . basename($file) . "\n";
                    } else {
                        echo "  ✗ Failed to delete: " . basename($file) . "\n";
                    }
                }
            }
        } else {
            echo "  ℹ Directory not found: {$dir}\n";
        }
    }
    
    $success[] = "Deleted {$filesDeleted} uploaded file(s)";
    echo "\n✅ Server files cleaned\n\n";
    
} catch (Exception $e) {
    $errors[] = "File cleanup failed: " . $e->getMessage();
    echo "\n❌ File cleanup failed: " . $e->getMessage() . "\n\n";
}

// ========================================
// FINAL SUMMARY
// ========================================

echo str_repeat('=', 70) . "\n";
echo "CLEANUP SUMMARY\n";
echo str_repeat('=', 70) . "\n\n";

if (!empty($success)) {
    echo "✅ SUCCESS:\n";
    foreach ($success as $msg) {
        echo "   • {$msg}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS:\n";
    foreach ($errors as $error) {
        echo "   • {$error}\n";
    }
    echo "\n";
}

echo "🧹 Database cleanup completed!\n\n";
echo "Next steps:\n";
echo "  1. Reinstall fresh schema:\n";
echo "     • User DB: mysql < sql/full_user_schema.sql\n";
echo "     • Content DB: mysql < sql/full_content_schema.sql\n";
echo "  2. Or apply migrations: php sql/apply_all_migrations_to_production.php\n";
echo "  3. Delete this dangerous script: rm sql/drop_and_clean_databases.php\n\n";

if (!empty($errors)) {
    exit(1);
}
