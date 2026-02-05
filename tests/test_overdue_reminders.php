<?php
/**
 * Test script for overdue reminder functionality
 * This script tests the new overdue reminder methods
 */

// This is a test file to verify the logic
// DO NOT RUN ON PRODUCTION DATABASE

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';
require_once __DIR__ . '/../src/MailService.php';

echo "=== Testing Overdue Reminder Functionality ===\n\n";

// Test 1: Check if new methods exist in Inventory model
echo "Test 1: Checking if new Inventory methods exist\n";
$inventoryMethods = [
    'getOverdueCheckoutsForReminders',
    'markReminderSent'
];

foreach ($inventoryMethods as $method) {
    if (method_exists('Inventory', $method)) {
        echo "✓ Method '$method' exists in Inventory model\n";
    } else {
        echo "✗ Method '$method' is missing in Inventory model\n";
    }
}

echo "\n";

// Test 2: Check if new method exists in MailService
echo "Test 2: Checking if new MailService method exists\n";
if (method_exists('MailService', 'sendInventoryOverdueReminder')) {
    echo "✓ Method 'sendInventoryOverdueReminder' exists in MailService\n";
} else {
    echo "✗ Method 'sendInventoryOverdueReminder' is missing in MailService\n";
}

echo "\n";

// Test 3: Check database schema
echo "Test 3: Checking if 'last_reminder_sent_at' column exists in rentals table\n";
try {
    $db = Database::getContentDB();
    $stmt = $db->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'rentals'
        AND COLUMN_NAME = 'last_reminder_sent_at'
    ");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Column 'last_reminder_sent_at' exists in rentals table\n";
    } else {
        echo "⚠ Column 'last_reminder_sent_at' does not exist. Please run: php sql/migrate_add_reminder_tracking.php\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking database: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test getOverdueCheckoutsForReminders (read-only operation)
echo "Test 4: Testing getOverdueCheckoutsForReminders method\n";
try {
    $overdueRentals = Inventory::getOverdueCheckoutsForReminders();
    echo "✓ Method executed successfully\n";
    echo "  Found " . count($overdueRentals) . " overdue rental(s) needing reminders\n";
    
    if (count($overdueRentals) > 0) {
        echo "  Sample data structure:\n";
        $sample = $overdueRentals[0];
        $keys = array_keys($sample);
        echo "  - Fields: " . implode(', ', $keys) . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Verify cron script exists
echo "Test 5: Checking if cron script exists\n";
$cronScript = __DIR__ . '/../cron/send_overdue_reminders.php';
if (file_exists($cronScript)) {
    echo "✓ Cron script exists at: cron/send_overdue_reminders.php\n";
    
    // Check if it's executable (on Unix systems)
    if (is_readable($cronScript)) {
        echo "✓ Cron script is readable\n";
    } else {
        echo "⚠ Cron script is not readable\n";
    }
} else {
    echo "✗ Cron script is missing\n";
}

echo "\n";

// Test 6: Verify migration script exists
echo "Test 6: Checking if migration script exists\n";
$migrationScript = __DIR__ . '/../sql/migrate_add_reminder_tracking.php';
if (file_exists($migrationScript)) {
    echo "✓ Migration script exists at: sql/migrate_add_reminder_tracking.php\n";
} else {
    echo "✗ Migration script is missing\n";
}

echo "\n";

echo "=== All Tests Completed ===\n";
echo "\nNext steps:\n";
echo "1. Run migration: php sql/migrate_add_reminder_tracking.php\n";
echo "2. Test cron script: php cron/send_overdue_reminders.php\n";
echo "3. Set up cron job to run the script daily\n";
