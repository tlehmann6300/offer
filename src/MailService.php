<?php
/**
 * Mail Service
 * Handles email sending using SMTP configuration
 */

require_once __DIR__ . '/../config/config.php';

class MailService {
    
    /**
     * Send helper confirmation email with ICS attachment
     * 
     * @param string $toEmail Recipient email address
     * @param string $toName Recipient name
     * @param array $event Event data
     * @param array|null $slot Slot data (if specific slot)
     * @param string $icsContent ICS file content
     * @param string $googleCalendarLink Google Calendar link
     * @return bool Success status
     */
    public static function sendHelperConfirmation($toEmail, $toName, $event, $slot, $icsContent, $googleCalendarLink) {
        $subject = "Einsatzbestätigung: " . $event['title'];
        
        // Build email body
        $body = self::buildHelperConfirmationBody($toName, $event, $slot, $googleCalendarLink);
        
        // Create filename for ICS attachment
        $icsFilename = 'event_' . $event['id'] . ($slot ? '_slot_' . $slot['id'] : '') . '.ics';
        
        // Send email with attachment
        return self::sendEmailWithAttachment(
            $toEmail,
            $toName,
            $subject,
            $body,
            $icsFilename,
            $icsContent
        );
    }
    
    /**
     * Build HTML email body for helper confirmation
     * 
     * @param string $userName User name
     * @param array $event Event data
     * @param array|null $slot Slot data
     * @param string $googleCalendarLink Google Calendar link
     * @return string HTML email body
     */
    private static function buildHelperConfirmationBody($userName, $event, $slot, $googleCalendarLink) {
        // Determine times
        if ($slot !== null) {
            $start = new DateTime($slot['start_time']);
            $end = new DateTime($slot['end_time']);
            $shiftInfo = $start->format('d.m.Y H:i') . ' - ' . $end->format('H:i');
        } else {
            $start = new DateTime($event['start_time']);
            $end = new DateTime($event['end_time']);
            $shiftInfo = $start->format('d.m.Y H:i') . ' - ' . $end->format('d.m.Y H:i');
        }
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #3B82F6; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .info-box { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #3B82F6; }
        .info-label { font-weight: bold; color: #3B82F6; }
        .button { display: inline-block; padding: 10px 20px; margin: 10px 0; background-color: #3B82F6; color: white; text-decoration: none; border-radius: 5px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Einsatzbestätigung</h1>
        </div>
        <div class="content">
            <p>Hallo ' . htmlspecialchars($userName) . ',</p>
            <p>vielen Dank für deine Anmeldung als Helfer! Hier sind die Details zu deinem Einsatz:</p>
            
            <div class="info-box">
                <p><span class="info-label">Event:</span> ' . htmlspecialchars($event['title']) . '</p>
                <p><span class="info-label">Deine Schicht:</span> ' . htmlspecialchars($shiftInfo) . '</p>';
        
        if (!empty($event['location'])) {
            $html .= '<p><span class="info-label">Ort:</span> ' . htmlspecialchars($event['location']) . '</p>';
        }
        
        if (!empty($event['contact_person'])) {
            $html .= '<p><span class="info-label">Kontaktperson:</span> ' . htmlspecialchars($event['contact_person']) . '</p>';
        }
        
        if (!empty($event['description'])) {
            $html .= '<p><span class="info-label">Beschreibung:</span><br>' . nl2br(htmlspecialchars($event['description'])) . '</p>';
        }
        
        $html .= '    </div>
            
            <p>Du kannst diesen Termin direkt zu deinem Kalender hinzufügen:</p>
            <p style="text-align: center;">
                <a href="' . htmlspecialchars($googleCalendarLink) . '" class="button" target="_blank">
                    Zu Google Calendar hinzufügen
                </a>
            </p>
            <p style="font-size: 12px; color: #666;">
                Die angehängte .ics-Datei kann in allen gängigen Kalender-Anwendungen (Outlook, Apple Calendar, etc.) verwendet werden.
            </p>
            
            <p>Wir freuen uns auf deinen Einsatz!</p>
        </div>
        <div class="footer">
            <p>Diese E-Mail wurde automatisch vom IBC Intranet Event-System generiert.</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Send email with attachment using PHP mail function with SMTP
     * 
     * @param string $toEmail Recipient email
     * @param string $toName Recipient name
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string $attachmentFilename Attachment filename
     * @param string $attachmentContent Attachment content
     * @return bool Success status
     */
    private static function sendEmailWithAttachment($toEmail, $toName, $subject, $htmlBody, $attachmentFilename, $attachmentContent) {
        // Generate email boundary
        $boundary = md5(time());
        
        // Prepare headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $headers[] = 'From: ' . SMTP_FROM;
        $headers[] = 'Reply-To: ' . SMTP_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        // Build email body with multipart
        $message = '';
        
        // HTML part
        $message .= '--' . $boundary . "\r\n";
        $message .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $message .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        // Attachment part
        $message .= '--' . $boundary . "\r\n";
        $message .= 'Content-Type: text/calendar; charset=UTF-8; name="' . $attachmentFilename . '"' . "\r\n";
        $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
        $message .= 'Content-Disposition: attachment; filename="' . $attachmentFilename . '"' . "\r\n\r\n";
        $message .= chunk_split(base64_encode($attachmentContent)) . "\r\n";
        $message .= '--' . $boundary . '--';
        
        // Set additional mail parameters for SMTP
        $additionalParameters = '-f ' . SMTP_FROM;
        
        // Send email
        try {
            $success = mail($toEmail, $subject, $message, implode("\r\n", $headers), $additionalParameters);
            
            if (!$success) {
                error_log("Failed to send email to {$toEmail}: mail() returned false");
                return false;
            }
            
            error_log("Successfully sent helper confirmation email to {$toEmail}");
            return true;
        } catch (Exception $e) {
            error_log("Error sending email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send a simple email (used for waitlist notifications, etc.)
     * 
     * @param string $toEmail Recipient email
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @return bool Success status
     */
    public static function sendEmail($toEmail, $subject, $htmlBody) {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . SMTP_FROM;
        $headers[] = 'Reply-To: ' . SMTP_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        $additionalParameters = '-f ' . SMTP_FROM;
        
        try {
            $success = mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers), $additionalParameters);
            
            if (!$success) {
                error_log("Failed to send email to {$toEmail}: mail() returned false");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error sending email to {$toEmail}: " . $e->getMessage());
            return false;
        }
    }
}
