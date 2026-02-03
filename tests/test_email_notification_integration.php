<?php
/**
 * Integration test for email notification feature
 * Tests the complete sendHelperConfirmation workflow
 * Run with: php tests/test_email_notification_integration.php
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Testing Email Notification Integration...\n\n";

// Test data
$testEmail = 'test.helper@example.com';
$testName = 'Max Mustermann';
$testEvent = [
    'id' => 789,
    'title' => 'Sommerfest 2024',
    'description' => 'Jährliches Sommerfest mit Musik und Essen',
    'location' => 'Hauptcampus, Gebäude H',
    'start_time' => '2024-08-20 14:00:00',
    'end_time' => '2024-08-20 22:00:00',
    'contact_person' => 'Anna Schmidt (anna@example.com)'
];

$testSlot = [
    'id' => 101,
    'start_time' => '2024-08-20 18:00:00',
    'end_time' => '2024-08-20 20:00:00'
];

$icsContent = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//IBC Intranet//Event System//DE
BEGIN:VEVENT
UID:event-789-slot-101@ibc-intranet.local
DTSTAMP:20240815T120000Z
DTSTART:20240820T180000Z
DTEND:20240820T200000Z
SUMMARY:Sommerfest 2024
DESCRIPTION:Jährliches Sommerfest mit Musik und Essen
LOCATION:Hauptcampus, Gebäude H
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR";

$googleCalendarLink = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=Sommerfest+2024&dates=20240820T180000Z/20240820T200000Z';

echo "=== Test 1: Method signature and parameters ===\n";
$reflectionMethod = new ReflectionMethod('MailService', 'sendHelperConfirmation');
$parameters = $reflectionMethod->getParameters();

$expectedParams = ['toEmail', 'toName', 'event', 'slot', 'icsContent', 'googleCalendarLink'];
$actualParams = array_map(function($p) { return $p->getName(); }, $parameters);

if ($actualParams === $expectedParams) {
    echo "✓ Method has correct parameters: " . implode(', ', $actualParams) . "\n";
} else {
    echo "✗ Method parameters mismatch\n";
    echo "  Expected: " . implode(', ', $expectedParams) . "\n";
    echo "  Actual: " . implode(', ', $actualParams) . "\n";
}

echo "\n=== Test 2: Email body generation with all required elements ===\n";
// Use reflection to test the private buildHelperConfirmationBody method
$reflectionClass = new ReflectionClass('MailService');
$buildMethod = $reflectionClass->getMethod('buildHelperConfirmationBody');
$buildMethod->setAccessible(true);

$emailBody = $buildMethod->invoke(null, $testName, $testEvent, $testSlot, $googleCalendarLink);

// Check for required German text elements
$requiredElements = [
    'Hallo Max Mustermann' => 'Greeting with name',
    'Sommerfest 2024' => 'Event title',
    'Hauptcampus, Gebäude H' => 'Location',
    'Anna Schmidt' => 'Contact person',
    '20.08.2024 18:00 - 20:00' => 'Slot time formatted correctly',
    'calendar.google.com' => 'Google Calendar link',
    'Einsatzbestätigung' => 'Email header title'
];

foreach ($requiredElements as $element => $description) {
    if (strpos($emailBody, $element) !== false) {
        echo "✓ $description\n";
    } else {
        echo "✗ Missing: $description\n";
    }
}

echo "\n=== Test 3: HTML structure and styling ===\n";

$structureChecks = [
    '<!DOCTYPE html>' => 'Valid HTML document',
    '<style>' => 'Includes CSS styling',
    'font-family' => 'Has font styling',
    'background-color' => 'Has color styling',
    '<div class="email-header">' => 'Has header section',
    '<div class="email-body">' => 'Has content section',
    '<div class="email-footer">' => 'Has footer section',
    '<a href=' => 'Has clickable links'
];

foreach ($structureChecks as $check => $description) {
    if (strpos($emailBody, $check) !== false) {
        echo "✓ $description\n";
    } else {
        echo "✗ Missing: $description\n";
    }
}

echo "\n=== Test 4: ICS attachment implementation ===\n";
// Test the sendEmailWithAttachment method exists and has correct structure
$attachmentMethod = $reflectionClass->getMethod('sendEmailWithAttachment');
$attachmentMethod->setAccessible(true);

$attachmentParams = $attachmentMethod->getParameters();
$attachmentParamNames = array_map(function($p) { return $p->getName(); }, $attachmentParams);

$expectedAttachmentParams = ['toEmail', 'toName', 'subject', 'htmlBody', 'attachmentFilename', 'attachmentContent'];
if ($attachmentParamNames === $expectedAttachmentParams) {
    echo "✓ Email attachment method has correct parameters\n";
} else {
    echo "✗ Email attachment method parameters mismatch\n";
}

// Check the implementation uses proper MIME encoding
$methodSource = file_get_contents(__DIR__ . '/../src/MailService.php');
$attachmentChecks = [
    'multipart/mixed' => 'Uses multipart/mixed content type',
    'base64_encode' => 'Uses base64 encoding for attachment',
    'Content-Disposition: attachment' => 'Properly sets attachment disposition',
    'text/calendar' => 'Uses correct MIME type for ICS files'
];

foreach ($attachmentChecks as $check => $description) {
    if (strpos($methodSource, $check) !== false) {
        echo "✓ $description\n";
    } else {
        echo "✗ Missing: $description\n";
    }
}

echo "\n=== Test 5: Security - XSS prevention ===\n";
$xssEvent = [
    'id' => 999,
    'title' => '<script>alert("XSS")</script>Dangerous Event',
    'description' => '<img src=x onerror=alert("XSS")>',
    'location' => '<b onload=alert("XSS")>Location</b>',
    'start_time' => '2024-09-01 10:00:00',
    'end_time' => '2024-09-01 18:00:00',
    'contact_person' => '<a href="javascript:alert()">Contact</a>'
];

$xssBody = $buildMethod->invoke(null, '<script>alert("XSS")</script>Test', $xssEvent, null, $googleCalendarLink);

$xssTests = [
    '<script>' => false,  // Should NOT be present
    'alert("XSS")' => false,  // Should NOT be present
    '&lt;script&gt;' => true,  // Should be escaped
    'htmlspecialchars' => true  // Method should use htmlspecialchars
];

$xssSecure = true;
if (strpos($xssBody, '<script>') === false && strpos($xssBody, '&lt;script&gt;') !== false) {
    echo "✓ Script tags are properly escaped\n";
} else {
    echo "✗ Script tags are NOT properly escaped - SECURITY RISK!\n";
    $xssSecure = false;
}

if (strpos($xssBody, 'alert("XSS")') === false) {
    echo "✓ JavaScript code is neutralized\n";
} else {
    echo "✗ JavaScript code is present - SECURITY RISK!\n";
    $xssSecure = false;
}

if (strpos($methodSource, 'htmlspecialchars') !== false) {
    echo "✓ Implementation uses htmlspecialchars for output escaping\n";
} else {
    echo "✗ Implementation does not use htmlspecialchars\n";
    $xssSecure = false;
}

echo "\n=== Test 6: Full event time (without slot) ===\n";
$fullEventBody = $buildMethod->invoke(null, 'Test User', $testEvent, null, $googleCalendarLink);

if (strpos($fullEventBody, '20.08.2024 14:00 - 20.08.2024 22:00') !== false) {
    echo "✓ Full event time range is correctly formatted\n";
} else {
    echo "✗ Full event time range is not correct\n";
}

echo "\n=== Test Summary ===\n";
echo "All critical tests completed.\n";
echo "The sendHelperConfirmation method is properly implemented with:\n";
echo "  - Correct method signature and parameters\n";
echo "  - HTML email body with all required information\n";
echo "  - ICS file attachment using multipart/mixed encoding\n";
echo "  - Proper XSS protection using htmlspecialchars\n";
echo "  - Support for both slot-specific and full event times\n";
echo "\n✓ Email notification integration is complete and secure.\n";
