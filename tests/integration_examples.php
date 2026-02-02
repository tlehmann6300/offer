<?php
/**
 * Integration Example: Complete Event Signup Flow with Email
 * This demonstrates how all components work together
 * 
 * Note: This is an example/documentation file, not a runnable test
 * as it requires database access and email configuration
 */

// ============================================
// EXAMPLE 1: Helper Slot Signup Flow
// ============================================

/*
// 1. User signs up for a helper slot via API
POST /api/event_signup.php
{
    "action": "signup",
    "event_id": 123,
    "slot_id": 456,
    "slot_start": "2024-07-15 14:00:00",
    "slot_end": "2024-07-15 16:00:00"
}

// 2. Backend processes the signup:

require_once __DIR__ . '/includes/handlers/AuthHandler.php';
require_once __DIR__ . '/includes/models/Event.php';
require_once __DIR__ . '/includes/models/User.php';
require_once __DIR__ . '/src/CalendarService.php';
require_once __DIR__ . '/src/MailService.php';

AuthHandler::startSession();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Process signup
$result = Event::signup($eventId, $userId, $slotId, $userRole);

// 3. If signup is confirmed, send email automatically:
if ($result['status'] === 'confirmed' && $slotId) {
    // Get event details
    $event = Event::getById($eventId, true);
    
    // Get user details
    $user = User::getById($userId);
    
    // Find the slot details
    $slotDetails = findSlotInEvent($event, $slotId);
    
    // Generate calendar content
    $icsContent = CalendarService::generateICS($event, $slotDetails);
    $googleLink = CalendarService::generateGoogleCalendarLink($event, $slotDetails);
    
    // Send confirmation email
    MailService::sendHelperConfirmation(
        $user['email'],
        $user['first_name'] . ' ' . $user['last_name'],
        $event,
        $slotDetails,
        $icsContent,
        $googleLink
    );
}

// 4. User receives email with:
// - Event details (title, description, location, contact)
// - Shift time (14:00 - 16:00)
// - ICS file attachment (opens in any calendar app)
// - Google Calendar "Add" button
// - Professional HTML template
*/

// ============================================
// EXAMPLE 2: Cancellation with Waitlist Promotion
// ============================================

/*
// 1. User cancels their confirmed slot
POST /api/event_signup.php
{
    "action": "cancel",
    "signup_id": 789
}

// 2. Backend processes:

$cancelResult = Event::cancelSignup($signupId, $userId);

// 3. If someone was on waitlist, they get promoted automatically:
if ($cancelResult['promoted_user_id']) {
    $promotedUserId = $cancelResult['promoted_user_id'];
    $eventId = $cancelResult['event_id'];
    $slotId = $cancelResult['slot_id'];
    
    // Get promoted user and event details
    $promotedUser = User::getById($promotedUserId);
    $event = Event::getById($eventId, true);
    $slotDetails = findSlotInEvent($event, $slotId);
    
    // Generate calendar content
    $icsContent = CalendarService::generateICS($event, $slotDetails);
    $googleLink = CalendarService::generateGoogleCalendarLink($event, $slotDetails);
    
    // Send confirmation to promoted user
    MailService::sendHelperConfirmation(
        $promotedUser['email'],
        $promotedUser['first_name'] . ' ' . $promotedUser['last_name'],
        $event,
        $slotDetails,
        $icsContent,
        $googleLink
    );
}

// 4. Promoted user receives confirmation email automatically
*/

// ============================================
// EXAMPLE 3: Status Update (Pseudo-Cron)
// ============================================

/*
// 1. User visits any page with pseudo-cron included:
require_once __DIR__ . '/includes/handlers/AuthHandler.php';
AuthHandler::startSession();

// Include pseudo-cron (runs max once per 5 minutes per session)
require_once __DIR__ . '/includes/pseudo_cron.php';

// 2. Behind the scenes, Event::updateEventStatuses() runs:
$updates = Event::updateEventStatuses();

// Example output:
// $updates = [
//     'planned_to_open' => 2,  // 2 events opened for registration
//     'to_past' => 5           // 5 events moved to past status
// ];

// 3. All event statuses are now current
*/

// ============================================
// EXAMPLE 4: Calendar Integration Types
// ============================================

/*
// ICS File Content (for any calendar app):
$icsContent = CalendarService::generateICS($event, $slot);

// Example output:
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//IBC Intranet//Event System//DE
CALSCALE:GREGORIAN
METHOD:PUBLISH
BEGIN:VEVENT
UID:abc123@ibc-intranet
DTSTAMP:20240715T120000Z
DTSTART:20240715T140000
DTEND:20240715T160000
SUMMARY:Summer Festival - Aufbau Team
DESCRIPTION:Setup team for the summer festival...
LOCATION:Main Campus\, Building H
STATUS:CONFIRMED
SEQUENCE:0
END:VEVENT
END:VCALENDAR

// Google Calendar Link (one-click add):
$googleLink = CalendarService::generateGoogleCalendarLink($event, $slot);

// Example output:
https://calendar.google.com/calendar/render?action=TEMPLATE&text=...

// User clicks the link and calendar opens with pre-filled event
*/

// ============================================
// EXAMPLE 5: Email Template Preview
// ============================================

/*
// The email sent to helpers includes:

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        .header { background: #3B82F6; color: white; padding: 20px; }
        .content { padding: 20px; background: #f9f9f9; }
        .info-box { background: white; padding: 15px; border-left: 4px solid #3B82F6; }
        .button { padding: 10px 20px; background: #3B82F6; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Einsatzbestätigung</h1>
        </div>
        <div class="content">
            <p>Hallo John Doe,</p>
            <p>vielen Dank für deine Anmeldung als Helfer!</p>
            
            <div class="info-box">
                <p><strong>Event:</strong> Summer Festival</p>
                <p><strong>Deine Schicht:</strong> 15.07.2024 14:00 - 16:00</p>
                <p><strong>Ort:</strong> Main Campus, Building H</p>
                <p><strong>Kontaktperson:</strong> Max Mustermann</p>
            </div>
            
            <p>
                <a href="[Google Calendar Link]" class="button">
                    Zu Google Calendar hinzufügen
                </a>
            </p>
            
            <p>Die angehängte .ics-Datei kann in allen gängigen Kalender-Anwendungen verwendet werden.</p>
        </div>
        <div class="footer">
            <p>Diese E-Mail wurde automatisch generiert.</p>
        </div>
    </div>
</body>
</html>

// Attachment: event_123_slot_456.ics
*/

// ============================================
// EXAMPLE 6: No Email for General Participants
// ============================================

/*
// When a user signs up for event participation (no slot):
POST /api/event_signup.php
{
    "action": "signup",
    "event_id": 123,
    "slot_id": null  // No slot = general participation
}

// Backend checks:
if ($slotId && $result['status'] === 'confirmed') {
    // Send email ONLY if slot_id is provided
    // No email sent for general participation
}

// Result: Helper signups get emails, general participants don't
*/

// ============================================
// Helper Functions (for examples above)
// ============================================

function findSlotInEvent($event, $slotId) {
    foreach ($event['helper_types'] as $helperType) {
        foreach ($helperType['slots'] as $slot) {
            if ($slot['id'] == $slotId) {
                $slot['slot_title'] = $helperType['title'];
                return $slot;
            }
        }
    }
    return null;
}

echo "Integration examples documented.\n";
echo "See AUTOMATION_DOCUMENTATION.md for complete documentation.\n";
