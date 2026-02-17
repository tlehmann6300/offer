<?php
/**
 * MailQueue Model
 * Manages the mail queue for scheduled email delivery
 */

require_once __DIR__ . '/../database.php';

class MailQueue {

    /**
     * Add a new entry to the mail queue
     *
     * @param int $eventId Event ID
     * @param string $email Recipient email address
     * @param string $name Recipient name
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string|null $icsContent Generated ICS content
     * @return bool True on success
     */
    public static function addToQueue($eventId, $email, $name, $subject, $body, $icsContent) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                INSERT INTO mail_queue (event_id, recipient_email, recipient_name, subject, body, ics_content)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $eventId,
                $email,
                $name,
                $subject,
                $body,
                $icsContent
            ]);
        } catch (Exception $e) {
            error_log("Error adding to mail queue: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the oldest pending entries from the queue
     *
     * @param int $limit Maximum number of entries to retrieve
     * @return array Array of pending mail queue entries
     */
    public static function getPending($limit) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                SELECT id, event_id, recipient_email, recipient_name, subject, body, ics_content, attempts
                FROM mail_queue
                WHERE status = 'pending'
                ORDER BY created_at ASC
                LIMIT ?
            ");
            $stmt->execute([(int)$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching pending mails: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark a mail queue entry as sent
     *
     * @param int $id Mail queue entry ID
     * @return bool True on success
     */
    public static function markAsSent($id) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                UPDATE mail_queue
                SET status = 'sent', sent_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error marking mail as sent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark a mail queue entry as failed
     *
     * @param int $id Mail queue entry ID
     * @param string $error Error message
     * @return bool True on success
     */
    public static function markAsFailed($id, $error) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                UPDATE mail_queue
                SET status = 'failed', error_message = ?, attempts = attempts + 1
                WHERE id = ?
            ");
            return $stmt->execute([$error, $id]);
        } catch (Exception $e) {
            error_log("Error marking mail as failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the number of mails sent in the last hour
     *
     * @return int Number of mails sent in the last hour
     */
    public static function getHourlyUsage() {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM mail_queue
                WHERE status = 'sent' AND sent_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error fetching hourly mail usage: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get mail queue statistics for the dashboard
     *
     * @return array Array with total, pending, sent, and failed counts
     */
    public static function getStats() {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'sent' AND DATE(sent_at) = CURDATE() THEN 1 ELSE 0 END) as sent_today,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM mail_queue
            ");
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error fetching mail queue stats: " . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'sent' => 0,
                'sent_today' => 0,
                'failed' => 0
            ];
        }
    }
}
