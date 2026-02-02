<?php
/**
 * Cleanup Structure Script
 * 
 * This script cleans up the SQL file structure for IBC Intranet live deployment:
 * - Deletes the sql/migrations/ folder and its contents
 * - Deletes all .sql files in the root directory
 * - Deletes all .sql files in sql/ except the two schema files
 * - Deletes itself after completion
 * 
 * IMPORTANT: This script should be run only once during finalization.
 */

echo "==============================================\n";
echo "IBC Intranet Structure Cleanup Script\n";
echo "==============================================\n\n";

// Define the paths
$rootDir = __DIR__;
$sqlDir = $rootDir . '/sql';
$migrationsDir = $sqlDir . '/migrations';

// Files to keep in the sql/ directory
$keepFiles = [
    'user_database_schema.sql',
    'content_database_schema.sql'
];

// Counter for deleted items
$deletedCount = 0;

// ============================================
// Step 1: Delete sql/migrations/ folder
// ============================================
echo "Step 1: Checking for sql/migrations/ folder...\n";
if (is_dir($migrationsDir)) {
    echo "Found migrations directory. Deleting...\n";
    
    // Recursively delete directory contents
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($migrationsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        $deleteFunc = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        if ($deleteFunc($fileinfo->getRealPath())) {
            $deletedCount++;
            echo "  Deleted: " . $fileinfo->getRealPath() . "\n";
        }
    }
    
    // Delete the migrations directory itself
    if (rmdir($migrationsDir)) {
        $deletedCount++;
        echo "  Deleted migrations directory\n";
    }
} else {
    echo "No migrations directory found. Skipping.\n";
}
echo "\n";

// ============================================
// Step 2: Delete all .sql files in root directory
// ============================================
echo "Step 2: Checking for .sql files in root directory...\n";
$rootSqlFiles = glob($rootDir . '/*.sql');
if (count($rootSqlFiles) > 0) {
    echo "Found " . count($rootSqlFiles) . " SQL file(s) in root directory. Deleting...\n";
    foreach ($rootSqlFiles as $file) {
        if (unlink($file)) {
            $deletedCount++;
            echo "  Deleted: " . basename($file) . "\n";
        }
    }
} else {
    echo "No SQL files found in root directory. Skipping.\n";
}
echo "\n";

// ============================================
// Step 3: Delete all .sql files in sql/ except schema files
// ============================================
echo "Step 3: Checking for old .sql files in sql/ directory...\n";
$sqlFiles = glob($sqlDir . '/*.sql');
if (count($sqlFiles) > 0) {
    $filesToDelete = array_filter($sqlFiles, function($file) use ($keepFiles) {
        return !in_array(basename($file), $keepFiles);
    });
    
    if (count($filesToDelete) > 0) {
        echo "Found " . count($filesToDelete) . " old SQL file(s) to delete...\n";
        foreach ($filesToDelete as $file) {
            if (unlink($file)) {
                $deletedCount++;
                echo "  Deleted: " . basename($file) . "\n";
            }
        }
    } else {
        echo "No old SQL files found to delete. Skipping.\n";
    }
} else {
    echo "No SQL files found in sql/ directory. Skipping.\n";
}
echo "\n";

// ============================================
// Step 4: Self-delete
// ============================================
echo "Step 4: Self-deleting cleanup script...\n";
if (unlink(__FILE__)) {
    $deletedCount++;
    echo "  Cleanup script deleted successfully!\n";
}
echo "\n";

// ============================================
// Summary
// ============================================
echo "==============================================\n";
echo "Cleanup completed successfully!\n";
echo "Total items deleted: " . $deletedCount . "\n";
echo "==============================================\n";
echo "\nRemaining SQL files in sql/ directory:\n";
echo "  - user_database_schema.sql\n";
echo "  - content_database_schema.sql\n";
echo "\n";
echo "IBC Intranet structure is now ready for live deployment.\n";