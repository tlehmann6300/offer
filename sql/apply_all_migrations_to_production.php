<?php
/**
 * Apply All Migrations to Production
 * 
 * This script applies all consolidated migrations from the SQL files
 * to the production databases:
 * - dbs15253086.sql -> User Database
 * - dbs15161271.sql -> Content Database
 */

require_once __DIR__ . '/../includes/database.php';

// Set execution time limit
set_time_limit(300);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║      Apply All Migrations to Production Databases           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$success = [];

try {
    // ========================================
    // STEP 1: Apply User Database Migrations
    // ========================================
    
    echo "STEP 1: Applying migrations to User Database (dbs15253086)\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $userDB = Database::getUserDB();
    $userSqlFile = __DIR__ . '/dbs15253086.sql';
    
    if (!file_exists($userSqlFile)) {
        throw new Exception("User database SQL file not found: {$userSqlFile}");
    }
    
    $userSql = file_get_contents($userSqlFile);
    
    // Split SQL file into individual statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;[\r\n]+/', $userSql)
        ),
        function($stmt) {
            // Filter out empty statements and comments
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   strlen($stmt) > 5;
        }
    );
    
    $userCount = 0;
    foreach ($statements as $statement) {
        try {
            // Skip comment lines
            if (preg_match('/^--/', $statement)) {
                continue;
            }
            
            $userDB->exec($statement);
            $userCount++;
            
            // Show progress
            if (preg_match('/ALTER TABLE (\w+)/i', $statement, $matches)) {
                echo "  ✓ Applied: ALTER TABLE {$matches[1]}\n";
            } elseif (preg_match('/CREATE TABLE (\w+)/i', $statement, $matches)) {
                echo "  ✓ Applied: CREATE TABLE {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Some errors are acceptable (e.g., column already exists)
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "  ℹ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            } else {
                $errors[] = "User DB: " . $e->getMessage();
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    $success[] = "Applied {$userCount} migration(s) to User Database";
    echo "\n✅ User Database migrations completed\n\n";
    
} catch (Exception $e) {
    $errors[] = "User DB Fatal: " . $e->getMessage();
    echo "\n❌ User Database migration failed: " . $e->getMessage() . "\n\n";
}

try {
    // ========================================
    // STEP 2: Apply Content Database Migrations
    // ========================================
    
    echo "STEP 2: Applying migrations to Content Database (dbs15161271)\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $contentDB = Database::getContentDB();
    $contentSqlFile = __DIR__ . '/dbs15161271.sql';
    
    if (!file_exists($contentSqlFile)) {
        throw new Exception("Content database SQL file not found: {$contentSqlFile}");
    }
    
    $contentSql = file_get_contents($contentSqlFile);
    
    // Split SQL file into individual statements
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;[\r\n]+/', $contentSql)
        ),
        function($stmt) {
            // Filter out empty statements and comments
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   strlen($stmt) > 5;
        }
    );
    
    $contentCount = 0;
    foreach ($statements as $statement) {
        try {
            // Skip comment lines
            if (preg_match('/^--/', $statement)) {
                continue;
            }
            
            $contentDB->exec($statement);
            $contentCount++;
            
            // Show progress
            if (preg_match('/ALTER TABLE (\w+)/i', $statement, $matches)) {
                echo "  ✓ Applied: ALTER TABLE {$matches[1]}\n";
            } elseif (preg_match('/CREATE TABLE (\w+)/i', $statement, $matches)) {
                echo "  ✓ Applied: CREATE TABLE {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // Some errors are acceptable (e.g., column already exists)
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), "Can't DROP") !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "  ℹ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            } else {
                $errors[] = "Content DB: " . $e->getMessage();
                echo "  ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    $success[] = "Applied {$contentCount} migration(s) to Content Database";
    echo "\n✅ Content Database migrations completed\n\n";
    
} catch (Exception $e) {
    $errors[] = "Content DB Fatal: " . $e->getMessage();
    echo "\n❌ Content Database migration failed: " . $e->getMessage() . "\n\n";
}

// ========================================
// FINAL SUMMARY
// ========================================

echo str_repeat('=', 70) . "\n";
echo "MIGRATION SUMMARY\n";
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
    exit(1);
}

echo "🎉 All migrations applied successfully!\n\n";
echo "Next steps:\n";
echo "  1. Verify database schema with: php verify_db_schema.php\n";
echo "  2. Test application functionality\n";
echo "  3. Delete this file for security: rm sql/apply_all_migrations_to_production.php\n\n";
