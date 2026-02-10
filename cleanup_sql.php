<?php
/**
 * SQL Cleanup Script
 * 
 * This script removes all SQL files from the sql/ directory
 * EXCEPT the three master database files:
 * - dbs15161271.sql (Content DB)
 * - dbs15251284.sql (Invoice DB)
 * - dbs15253086.sql (User DB)
 * 
 * Usage: php cleanup_sql.php
 */

// Define the master files that should NOT be deleted
$masterFiles = [
    'dbs15161271.sql',  // Content DB
    'dbs15251284.sql',  // Invoice DB
    'dbs15253086.sql',  // User DB
];

// Define the SQL directory path
$sqlDir = __DIR__ . '/sql/';

// Check if directory exists
if (!is_dir($sqlDir)) {
    echo "Error: sql/ directory not found at: {$sqlDir}\n";
    exit(1);
}

// Get all SQL files in the directory
$sqlFiles = glob($sqlDir . '*.sql');

if (empty($sqlFiles)) {
    echo "No SQL files found in sql/ directory.\n";
    exit(0);
}

echo "SQL Cleanup Script\n";
echo "==================\n\n";
echo "Master files (will NOT be deleted):\n";
foreach ($masterFiles as $file) {
    echo "  - {$file}\n";
}
echo "\n";

// Track files to be deleted
$filesToDelete = [];
$filesToKeep = [];

foreach ($sqlFiles as $filePath) {
    $fileName = basename($filePath);
    
    if (in_array($fileName, $masterFiles)) {
        $filesToKeep[] = $fileName;
    } else {
        $filesToDelete[] = $fileName;
    }
}

// Display summary
echo "Files to keep: " . count($filesToKeep) . "\n";
foreach ($filesToKeep as $file) {
    echo "  ✓ {$file}\n";
}
echo "\n";

echo "Files to delete: " . count($filesToDelete) . "\n";
foreach ($filesToDelete as $file) {
    echo "  ✗ {$file}\n";
}
echo "\n";

// Ask for confirmation
echo "Do you want to proceed with deletion? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\nOperation cancelled.\n";
    exit(0);
}

// Perform deletion
echo "\nDeleting files...\n";
$deletedCount = 0;
$errorCount = 0;

foreach ($filesToDelete as $fileName) {
    $filePath = $sqlDir . $fileName;
    
    if (unlink($filePath)) {
        echo "  ✓ Deleted: {$fileName}\n";
        $deletedCount++;
    } else {
        echo "  ✗ Failed to delete: {$fileName}\n";
        $errorCount++;
    }
}

echo "\n";
echo "Cleanup complete!\n";
echo "  Deleted: {$deletedCount} file(s)\n";
echo "  Errors: {$errorCount} file(s)\n";
echo "  Kept: " . count($filesToKeep) . " master file(s)\n";

exit($errorCount > 0 ? 1 : 0);
