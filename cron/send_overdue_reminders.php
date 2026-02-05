<?php
/**
 * Overdue Inventory Reminders Cron Script
 * 
 * Sends reminder emails to users with overdue inventory items.
 * Limits to max 20 emails per execution to avoid SMTP timeouts/blocks.
 * Only sends reminders if the user hasn't been reminded in the last 24 hours.
 * 
 * Usage: php cron/send_overdue_reminders.php
 */

// Load required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';
require_once __DIR__ . '/../src/MailService.php';

// Output start message
echo "=== Overdue Inventory Reminder Email Cron Job ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

// Fetch overdue rentals that need reminders
$overdueRentals = Inventory::getOverdueCheckoutsForReminders();

$totalOverdue = count($overdueRentals);
echo "Found {$totalOverdue} overdue rentals that need reminders.\n";

// Limit to max 20 emails per execution
$maxEmails = 20;
$rentalsToProcess = array_slice($overdueRentals, 0, $maxEmails);

echo "Processing " . count($rentalsToProcess) . " rentals (max {$maxEmails} per execution).\n\n";

$emailsSent = 0;
$emailsFailed = 0;

// Loop through rentals and send reminder emails
foreach ($rentalsToProcess as $rental) {
    $userName = $rental['user_name'];
    $userEmail = $rental['user_email'];
    $itemName = $rental['item_name'];
    $expectedReturn = $rental['expected_return'];
    $rentalId = $rental['rental_id'];
    
    // Skip if no email address
    if (empty($userEmail)) {
        echo "Skipping rental #{$rentalId}: No email address for user\n";
        $emailsFailed++;
        continue;
    }
    
    echo "Sending reminder to: {$userName} ({$userEmail}) for '{$itemName}'... ";
    
    // Send email using MailService
    try {
        $success = MailService::sendInventoryOverdueReminder(
            $userEmail,
            $userName,
            $itemName,
            $expectedReturn
        );
        
        if ($success) {
            // Mark reminder as sent to prevent re-sending
            Inventory::markReminderSent($rentalId);
            $emailsSent++;
            echo "SUCCESS\n";
        } else {
            $emailsFailed++;
            echo "FAILED\n";
        }
    } catch (Exception $e) {
        $emailsFailed++;
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    // Small delay between emails to avoid overwhelming SMTP server
    usleep(100000); // 0.1 second delay
}

// Output summary
echo "\n=== Summary ===\n";
echo "Total overdue rentals: {$totalOverdue}\n";
echo "Emails sent successfully: {$emailsSent}\n";
echo "Emails failed: {$emailsFailed}\n";
echo "Remaining rentals: " . max(0, $totalOverdue - $maxEmails) . "\n";
echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
