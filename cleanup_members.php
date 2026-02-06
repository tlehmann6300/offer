<?php
/**
 * Members Module Cleanup Script
 * 
 * This script performs cleanup operations for the Members module:
 * - Deletes pages/members/directory.php (deprecated file)
 * 
 * Run this script from the root directory:
 * php cleanup_members.php
 */

echo "=== Members Module Cleanup Script ===\n\n";

// Define the file to be deleted
$fileToDelete = __DIR__ . '/pages/members/directory.php';

echo "Step 1: Checking if directory.php exists...\n";
if (file_exists($fileToDelete)) {
    echo "✓ Found: $fileToDelete\n";
    
    echo "\nStep 2: Attempting to delete directory.php...\n";
    if (unlink($fileToDelete)) {
        echo "✓ Successfully deleted: $fileToDelete\n";
    } else {
        echo "✗ Failed to delete: $fileToDelete\n";
        exit(1);
    }
} else {
    echo "ℹ File does not exist: $fileToDelete\n";
    echo "  (Already cleaned up or never existed)\n";
}

echo "\n=== Cleanup Complete ===\n";
echo "The Members module has been cleaned up successfully.\n";
echo "Only pages/members/index.php remains, as intended.\n";
