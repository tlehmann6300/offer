<?php
/**
 * Mail Service
 * Handles email sending using SMTP configuration with IBC Corporate Design
 */

require_once __DIR__ . '/../config/config.php';

class MailService {
    
    /**
     * Get the professional HTML email template with IBC corporate design
     * 
     * @param string $title Email title/heading
     * @param string $bodyContent Main body content (HTML)
     * @param string|null $callToAction Optional call-to-action button HTML
     * @return string Complete HTML email template
     */
    private static function getTemplate($title, $bodyContent, $callToAction = null) {
        $html = '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .email-header {
            background-color: #20234A;
            padding: 30px 20px;
            text-align: center;
        }
        .email-header img {
            max-width: 200px;
            height: auto;
        }
        .email-body {
            padding: 30px 40px;
        }
        .email-title {
            color: #6D9744;
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 20px 0;
        }
        .email-text {
            color: #333;
            font-size: 16px;
            margin: 15px 0;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: #f9fafb;
            border-radius: 6px;
            overflow: hidden;
        }
        .info-table tr {
            border-bottom: 1px solid #e5e7eb;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 12px 15px;
            font-size: 15px;
        }
        .info-table td:first-child {
            font-weight: bold;
            color: #6D9744;
            width: 35%;
        }
        .button-container {
            text-align: center;
            margin: 25px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #6D9744;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .button:hover {
            background-color: #5d8038;
        }
        .email-footer {
            background-color: #f3f4f6;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .email-footer a {
            color: #6D9744;
            text-decoration: none;
        }
        .email-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <img src="cid:ibc_logo" alt="IBC Logo" />
            </div>
            <div class="email-body">
                <h1 class="email-title">' . htmlspecialchars($title) . '</h1>
                ' . $bodyContent . '
                ' . ($callToAction ? '<div class="button-container">' . $callToAction . '</div>' : '') . '
            </div>
            <div class="email-footer">
                <p>Diese E-Mail wurde automatisch vom IBC Intranet generiert.</p>
                <p><a href="' . BASE_URL . '">IBC Intranet</a> | <a href="' . BASE_URL . '/pages/impressum.php">Impressum</a></p>
                <p>&copy; ' . date('Y') . ' IBC - Alle Rechte vorbehalten.</p>
            </div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Send helper confirmation email with ICS attachment (Updated with new template)
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
        
        // Build email body with new template
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
     * Build HTML email body for helper confirmation using new template
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
            $when = $start->format('d.m.Y H:i') . ' - ' . $end->format('H:i');
            $role = 'Helfer';
        } else {
            $start = new DateTime($event['start_time']);
            $end = new DateTime($event['end_time']);
            $when = $start->format('d.m.Y H:i') . ' - ' . $end->format('d.m.Y H:i');
            $role = 'Helfer';
        }
        
        // Build body content
        $bodyContent = '<p class="email-text">Hallo ' . htmlspecialchars($userName) . ',</p>
        <p class="email-text">vielen Dank für deine Anmeldung! Hier sind die Details zu deinem Einsatz:</p>
        
        <table class="info-table">
            <tr>
                <td>Event</td>
                <td>' . htmlspecialchars($event['title']) . '</td>
            </tr>
            <tr>
                <td>Wann</td>
                <td>' . htmlspecialchars($when) . '</td>
            </tr>';
        
        if (!empty($event['location'])) {
            $bodyContent .= '<tr>
                <td>Wo</td>
                <td>' . htmlspecialchars($event['location']) . '</td>
            </tr>';
        }
        
        $bodyContent .= '<tr>
                <td>Rolle</td>
                <td>' . htmlspecialchars($role) . '</td>
            </tr>';
        
        if (!empty($event['contact_person'])) {
            $bodyContent .= '<tr>
                <td>Kontaktperson</td>
                <td>' . htmlspecialchars($event['contact_person']) . '</td>
            </tr>';
        }
        
        $bodyContent .= '</table>';
        
        if (!empty($event['description'])) {
            $bodyContent .= '<p class="email-text"><strong>Beschreibung:</strong><br>' . nl2br(htmlspecialchars($event['description'])) . '</p>';
        }
        
        $bodyContent .= '<p class="email-text">Die angehängte .ics-Datei kann in allen gängigen Kalender-Anwendungen (Outlook, Apple Calendar, etc.) verwendet werden.</p>
        <p class="email-text">Wir freuen uns auf deinen Einsatz!</p>';
        
        // Create call-to-action button for Google Calendar
        $callToAction = '<a href="' . htmlspecialchars($googleCalendarLink) . '" class="button" target="_blank">In Kalender speichern</a>';
        
        return self::getTemplate('Einsatzbestätigung', $bodyContent, $callToAction);
    }
    
    /**
     * Send invitation email with registration token (New method)
     * 
     * @param string $email Recipient email address
     * @param string $token Registration token
     * @param string $role User role (e.g., 'helper', 'admin', etc.)
     * @return bool Success status
     */
    public static function sendInvitation($email, $token, $role) {
        $subject = "Einladung zum IBC Intranet";
        
        // Build registration link
        $registrationLink = BASE_URL . '/pages/auth/register.php?token=' . urlencode($token);
        
        // Build body content
        $bodyContent = '<p class="email-text">Hallo,</p>
        <p class="email-text">du wurdest als <strong>' . htmlspecialchars(ucfirst($role)) . '</strong> zum IBC Intranet eingeladen.</p>
        <p class="email-text">Um dein Konto zu erstellen und Zugang zum System zu erhalten, klicke bitte auf den folgenden Button:</p>';
        
        // Create call-to-action button
        $callToAction = '<a href="' . htmlspecialchars($registrationLink) . '" class="button">Jetzt registrieren</a>';
        
        $bodyContent .= '<p class="email-text" style="margin-top: 20px; font-size: 14px; color: #6b7280;">Dieser Einladungslink ist nur einmal verwendbar. Falls du Probleme beim Registrieren hast, wende dich bitte an den Administrator.</p>';
        
        // Get complete HTML template
        $htmlBody = self::getTemplate('Einladung zum IBC Intranet', $bodyContent, $callToAction);
        
        // Send email without attachment but with embedded logo
        return self::sendEmailWithEmbeddedImage($email, $subject, $htmlBody);
    }
    
    /**
     * Send email with attachment and embedded logo using PHP mail function with SMTP
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
        // Generate email boundaries
        $mixedBoundary = md5(time() . 'mixed');
        $relatedBoundary = md5(time() . 'related');
        
        // Load logo file with robust path handling
        $imagePath = __DIR__ . '/../assets/img/ibc_logo_original_navbar.webp';
        if (!file_exists($imagePath)) {
            // Try png version
            $imagePath = __DIR__ . '/../assets/img/ibc_logo_original_navbar.png';
        }
        
        $logoContent = '';
        $logoMimeType = 'image/webp'; // Default to webp since we check it first
        
        if (file_exists($imagePath)) {
            $logoContent = file_get_contents($imagePath);
            // Update MIME type based on actual file
            if (strpos($imagePath, '.webp') !== false) {
                $logoMimeType = 'image/webp';
            } else {
                $logoMimeType = 'image/png';
            }
        }
        
        // Prepare headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';
        $headers[] = 'From: ' . SMTP_FROM;
        $headers[] = 'Reply-To: ' . SMTP_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        // Build email body with multipart/mixed (for attachment) and multipart/related (for embedded image)
        $message = '';
        
        // Start mixed boundary (outer)
        $message .= '--' . $mixedBoundary . "\r\n";
        $message .= 'Content-Type: multipart/related; boundary="' . $relatedBoundary . '"' . "\r\n\r\n";
        
        // HTML part (inside related boundary)
        $message .= '--' . $relatedBoundary . "\r\n";
        $message .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $message .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        // Embedded logo (inside related boundary)
        if (!empty($logoContent)) {
            $message .= '--' . $relatedBoundary . "\r\n";
            $message .= 'Content-Type: ' . $logoMimeType . '; name="logo.png"' . "\r\n";
            $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
            $message .= 'Content-ID: <ibc_logo>' . "\r\n";
            $message .= 'Content-Disposition: inline; filename="logo.png"' . "\r\n\r\n";
            $message .= chunk_split(base64_encode($logoContent)) . "\r\n";
        }
        
        // Close related boundary
        $message .= '--' . $relatedBoundary . '--' . "\r\n\r\n";
        
        // Attachment part (inside mixed boundary)
        $message .= '--' . $mixedBoundary . "\r\n";
        $message .= 'Content-Type: text/calendar; charset=UTF-8; name="' . $attachmentFilename . '"' . "\r\n";
        $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
        $message .= 'Content-Disposition: attachment; filename="' . $attachmentFilename . '"' . "\r\n\r\n";
        $message .= chunk_split(base64_encode($attachmentContent)) . "\r\n";
        
        // Close mixed boundary
        $message .= '--' . $mixedBoundary . '--';
        
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
     * Send email with embedded logo (no attachment)
     * 
     * @param string $toEmail Recipient email
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @return bool Success status
     */
    private static function sendEmailWithEmbeddedImage($toEmail, $subject, $htmlBody) {
        // Generate email boundary
        $relatedBoundary = md5(time() . 'related');
        
        // Load logo file with robust path handling
        $imagePath = __DIR__ . '/../assets/img/ibc_logo_original_navbar.webp';
        if (!file_exists($imagePath)) {
            // Try png version
            $imagePath = __DIR__ . '/../assets/img/ibc_logo_original_navbar.png';
        }
        
        $logoContent = '';
        $logoMimeType = 'image/webp'; // Default to webp since we check it first
        
        if (file_exists($imagePath)) {
            $logoContent = file_get_contents($imagePath);
            // Update MIME type based on actual file
            if (strpos($imagePath, '.webp') !== false) {
                $logoMimeType = 'image/webp';
            } else {
                $logoMimeType = 'image/png';
            }
        }
        
        // Prepare headers
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/related; boundary="' . $relatedBoundary . '"';
        $headers[] = 'From: ' . SMTP_FROM;
        $headers[] = 'Reply-To: ' . SMTP_FROM;
        $headers[] = 'X-Mailer: PHP/' . phpversion();
        
        // Build email body with multipart/related (for embedded image)
        $message = '';
        
        // HTML part
        $message .= '--' . $relatedBoundary . "\r\n";
        $message .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
        $message .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        // Embedded logo
        if (!empty($logoContent)) {
            $message .= '--' . $relatedBoundary . "\r\n";
            $message .= 'Content-Type: ' . $logoMimeType . '; name="logo.png"' . "\r\n";
            $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
            $message .= 'Content-ID: <ibc_logo>' . "\r\n";
            $message .= 'Content-Disposition: inline; filename="logo.png"' . "\r\n\r\n";
            $message .= chunk_split(base64_encode($logoContent)) . "\r\n";
        }
        
        // Close related boundary
        $message .= '--' . $relatedBoundary . '--';
        
        // Set additional mail parameters for SMTP
        $additionalParameters = '-f ' . SMTP_FROM;
        
        // Send email
        try {
            $success = mail($toEmail, $subject, $message, implode("\r\n", $headers), $additionalParameters);
            
            if (!$success) {
                error_log("Failed to send email to {$toEmail}: mail() returned false");
                return false;
            }
            
            error_log("Successfully sent invitation email to {$toEmail}");
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
    
    /**
     * Send project acceptance notification email
     * 
     * @param string $toEmail Recipient email address
     * @param array $project Project data
     * @param string $role Assigned role (lead or member)
     * @return bool Success status
     */
    public static function sendProjectAcceptance($toEmail, $project, $role) {
        
        $subject = "Projektzusage: " . $project['title'];
        
        // Build body content
        $roleName = ($role === 'lead') ? 'Projektleitung' : 'Projektmitglied';
        $bodyContent = '<p class="email-text">Hallo,</p>
        <p class="email-text">deine Bewerbung für das Projekt "<strong>' . htmlspecialchars($project['title']) . '</strong>" wurde akzeptiert!</p>
        <p class="email-text">Du wurdest als <strong>' . htmlspecialchars($roleName) . '</strong> zum Projekt hinzugefügt.</p>
        <p class="email-text">Weitere Details zum Projekt findest du im IBC Intranet.</p>';
        
        // Create call-to-action button
        $projectLink = BASE_URL . '/pages/projects/manage.php';
        $callToAction = '<a href="' . htmlspecialchars($projectLink) . '" class="button">Zum Projekt</a>';
        
        // Get complete HTML template
        $htmlBody = self::getTemplate('Projektzusage', $bodyContent, $callToAction);
        
        // Send email
        return self::sendEmailWithEmbeddedImage($toEmail, $subject, $htmlBody);
    }
    
    /**
     * Send project application status email (accepted or rejected)
     * 
     * @param string $userEmail Recipient email address
     * @param string $projectTitle Project title
     * @param string $status Status: 'accepted' or 'rejected'
     * @param array|null $clientData Client data with 'name' and 'contact' keys (only for accepted status)
     * @return bool Success status
     */
    public static function sendProjectApplicationStatus($userEmail, $projectTitle, $status, $clientData = null) {
        if ($status === 'accepted') {
            $subject = "Projektzusage: " . $projectTitle;
            $htmlBody = self::buildProjectApplicationAcceptedBody($projectTitle, $clientData);
            return self::sendEmailWithEmbeddedImage($userEmail, $subject, $htmlBody);
        } elseif ($status === 'rejected') {
            $subject = "Projektbewerbung: " . $projectTitle;
            $htmlBody = self::buildProjectApplicationRejectedBody($projectTitle);
            return self::sendEmailWithEmbeddedImage($userEmail, $subject, $htmlBody);
        }
        
        error_log("Invalid status '{$status}' for sendProjectApplicationStatus");
        return false;
    }
    
    /**
     * Build HTML body for project application acceptance email
     * 
     * @param string $projectTitle Project title
     * @param array|null $clientData Client data with 'name' and 'contact' keys
     * @return string HTML email body
     */
    private static function buildProjectApplicationAcceptedBody($projectTitle, $clientData) {
        // Build body content
        $bodyContent = '<p class="email-text">Hallo,</p>
        <p class="email-text">wir freuen uns, dir mitteilen zu können, dass deine Bewerbung für das Projekt "<strong>' . htmlspecialchars($projectTitle) . '</strong>" <strong>angenommen</strong> wurde!</p>';
        
        // Add client data if provided
        if ($clientData !== null && is_array($clientData)) {
            $bodyContent .= '<p class="email-text">Nachfolgend findest du die Kontaktdaten des Auftraggebers:</p>
            <table class="info-table">
                <tr>
                    <td>Name</td>
                    <td>' . htmlspecialchars($clientData['name'] ?? 'N/A') . '</td>
                </tr>
                <tr>
                    <td>Kontakt</td>
                    <td>' . htmlspecialchars($clientData['contact'] ?? 'N/A') . '</td>
                </tr>
            </table>';
        }
        
        // Add confidentiality notice
        $bodyContent .= '<p class="email-text" style="margin-top: 25px; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">
            <strong>⚠️ Vertraulichkeit:</strong><br>
            Bitte behandle alle Informationen zu diesem Projekt und insbesondere die Kundendaten streng vertraulich. 
            Diese Informationen dürfen nicht an Dritte weitergegeben werden.
        </p>';
        
        $bodyContent .= '<p class="email-text">Weitere Details zum Projekt findest du im IBC Intranet.</p>';
        
        // Create call-to-action button
        $projectLink = BASE_URL . '/pages/projects/manage.php';
        $callToAction = '<a href="' . htmlspecialchars($projectLink) . '" class="button">Zum Projekt</a>';
        
        // Get complete HTML template
        return self::getTemplate('Projektzusage', $bodyContent, $callToAction);
    }
    
    /**
     * Build HTML body for project application rejection email
     * 
     * @param string $projectTitle Project title
     * @return string HTML email body
     */
    private static function buildProjectApplicationRejectedBody($projectTitle) {
        // Build body content
        $bodyContent = '<p class="email-text">Hallo,</p>
        <p class="email-text">vielen Dank für dein Interesse am Projekt "<strong>' . htmlspecialchars($projectTitle) . '</strong>".</p>
        <p class="email-text">Leider müssen wir dir mitteilen, dass wir uns diesmal für andere Bewerber entschieden haben.</p>
        <p class="email-text">Diese Entscheidung war nicht einfach und bedeutet keine Bewertung deiner Fähigkeiten. 
        Wir ermutigen dich, dich auch in Zukunft auf weitere interessante Projekte zu bewerben.</p>
        <p class="email-text">Bei Fragen stehen wir dir gerne zur Verfügung.</p>
        <p class="email-text">Wir wünschen dir weiterhin viel Erfolg!</p>';
        
        // Create call-to-action button
        $projectsLink = BASE_URL . '/pages/projects/index.php';
        $callToAction = '<a href="' . htmlspecialchars($projectsLink) . '" class="button">Weitere Projekte ansehen</a>';
        
        // Get complete HTML template
        return self::getTemplate('Projektbewerbung', $bodyContent, $callToAction);
    }
}
