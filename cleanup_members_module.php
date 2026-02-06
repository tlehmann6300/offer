<?php
/**
 * Cleanup Members Module
 * 
 * This script deletes pages/members/directory.php if it exists.
 * This is part of consolidating the Members module to use strictly pages/members/index.php.
 */

// Define the path to the directory.php file
$directoryFilePath = __DIR__ . '/pages/members/directory.php';

// Check if the file exists
if (file_exists($directoryFilePath)) {
    // Attempt to delete the file
    if (unlink($directoryFilePath)) {
        echo "✓ Successfully deleted: pages/members/directory.php\n";
        exit(0);
    } else {
        echo "✗ Error: Failed to delete pages/members/directory.php\n";
        exit(1);
    }
} else {
    echo "ℹ Info: pages/members/directory.php does not exist (nothing to delete)\n";
    exit(0);
}
