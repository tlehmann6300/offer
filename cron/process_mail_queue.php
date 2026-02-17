<?php
/**
 * Mail Queue Processing Cron Script
 * 
 * Processes pending emails from the mail queue with hourly rate limiting.
 * Sends emails with ICS calendar attachments via MailService.
 * 
 * Usage: php cron/process_mail_queue.php
 */

// Load required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MailService.php';
require_once __DIR__ . '/../includes/models/MailQueue.php';

// 1. Limitierung
$hourlyLimit = 450;
$used = MailQueue::getHourlyUsage();
$remaining = $hourlyLimit - $used;

if ($remaining <= 0) {
    echo "Hourly limit reached\n";
    exit(0);
}

// 2. Verarbeitung
$pendingMails = MailQueue::getPending($remaining);
$processed = 0;

foreach ($pendingMails as $mail) {
    $id = $mail['id'];

    try {
        // Determine ICS filename
        $icsFilename = 'event_' . $mail['event_id'] . '.ics';

        // Send email with ICS string attachment
        $success = MailService::sendEmailWithAttachment(
            $mail['recipient_email'],
            $mail['recipient_name'],
            $mail['subject'],
            $mail['body'],
            $icsFilename,
            $mail['ics_content']
        );

        if ($success) {
            MailQueue::markAsSent($id);
        } else {
            MailQueue::markAsFailed($id, 'Send returned false');
        }
    } catch (Exception $e) {
        MailQueue::markAsFailed($id, $e->getMessage());
    }

    $processed++;

    // 0.2s delay to smooth server load
    usleep(200000);
}

// 3. Output
$limitRemaining = $remaining - $processed;
echo "Verarbeitet: {$processed} Mails. Limit verbleibend: {$limitRemaining}.\n";
