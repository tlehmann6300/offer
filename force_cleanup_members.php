<?php
/**
 * Force Cleanup Members Module
 * 
 * This script:
 * 1. Checks if pages/members/directory.php exists and deletes it
 * 2. Verifies if alumni_profiles table has the study_program column
 */

require_once __DIR__ . '/includes/database.php';

echo "=== Force Cleanup Members Module ===\n\n";

// Step 1: Check and delete pages/members/directory.php
$directoryFile = __DIR__ . '/pages/members/directory.php';
if (file_exists($directoryFile)) {
    if (unlink($directoryFile)) {
        echo "✅ Old directory.php deleted.\n";
    } else {
        echo "❌ Failed to delete directory.php\n";
    }
} else {
    echo "ℹ️ File was already gone.\n";
}

// Step 2: Check if alumni_profiles table has study_program column
echo "\nChecking database schema...\n";
try {
    $db = Database::getUserDB();
    
    // Query to check if study_program column exists
    $stmt = $db->query("SHOW COLUMNS FROM alumni_profiles LIKE 'study_program'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✅ Column 'study_program' exists in alumni_profiles table.\n";
    } else {
        echo "⚠️ WARNING: DB Column study_program missing! Run migrations first!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: Unable to verify database schema.\n";
    echo "   Error details: " . $e->getMessage() . "\n";
}

echo "\n=== Cleanup completed ===\n";
