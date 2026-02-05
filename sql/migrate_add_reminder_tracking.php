<?php
/**
 * Migration Script - Add last_reminder_sent_at to rentals table
 * Run this script to add tracking for overdue reminder emails
 * 
 * Usage: php sql/migrate_add_reminder_tracking.php
 */

require_once __DIR__ . '/../includes/database.php';

echo "=== Rentals Table Migration Script ===\n\n";

try {
    $db = Database::getContentDB();
    
    echo "Connecting to database...\n";
    
    // Check if column already exists
    $stmt = $db->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'rentals'
    ");
    $stmt->execute();
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
    
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n\n";
    
    // Add last_reminder_sent_at column if it doesn't exist
    if (!in_array('last_reminder_sent_at', $existingColumns)) {
        echo "Adding 'last_reminder_sent_at' column...\n";
        $db->exec("ALTER TABLE rentals ADD COLUMN last_reminder_sent_at DATETIME DEFAULT NULL AFTER actual_return");
        echo "✓ 'last_reminder_sent_at' column added successfully\n\n";
    } else {
        echo "✓ 'last_reminder_sent_at' column already exists\n\n";
    }
    
    echo "=== Migration Completed Successfully ===\n";
    echo "\nNext steps:\n";
    echo "1. The cron script can now track when reminder emails are sent\n";
    echo "2. Run cron/send_overdue_reminders.php to send reminders to users with overdue items\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    return;
}
