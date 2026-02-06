<?php
/**
 * Migration: Fix notification defaults for event notifications
 * 
 * This migration:
 * 1. Changes the default value of 'notify_new_events' column to TRUE (1)
 * 2. Updates all existing users to have notify_new_events = 1
 */

require_once __DIR__ . '/../src/Database.php';

try {
    echo "Starting migration: Fix notification defaults\n";
    echo "=============================================================\n\n";
    
    // Get user database connection
    $userDB = Database::getUserDB();
    
    // 1. Alter the users table to change the default value
    echo "1. Changing default value of notify_new_events column to TRUE...\n";
    try {
        $userDB->exec("
            ALTER TABLE users 
            MODIFY COLUMN notify_new_events BOOLEAN NOT NULL DEFAULT TRUE
        ");
        echo "   ✓ Changed default value of notify_new_events to TRUE\n\n";
    } catch (PDOException $e) {
        echo "   ⚠ Warning: Could not modify column default: " . $e->getMessage() . "\n\n";
    }
    
    // 2. Update all existing users to have notify_new_events = 1
    echo "2. Updating all existing users to enable event notifications...\n";
    $stmt = $userDB->exec("
        UPDATE users 
        SET notify_new_events = 1
    ");
    echo "   ✓ Updated all existing users\n\n";
    
    echo "=============================================================\n";
    echo "✅ Event notifications enabled by default for all users.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
