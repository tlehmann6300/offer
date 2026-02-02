<?php
/**
 * Simple unit test for MailService email body generation
 * Run with: php tests/test_mail_service.php
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Testing MailService...\n\n";

// Use reflection to test private method
$reflectionClass = new ReflectionClass('MailService');
$method = $reflectionClass->getMethod('buildHelperConfirmationBody');
$method->setAccessible(true);

// Test data
$testEvent = [
    'id' => 123,
    'title' => 'Summer Festival 2024',
    'description' => 'Annual summer festival with music and food',
    'location' => 'Main Campus, Building H',
    'start_time' => '2024-07-15 10:00:00',
    'end_time' => '2024-07-15 18:00:00',
    'contact_person' => 'Max Mustermann (max@example.com)'
];

$testSlot = [
    'id' => 456,
    'start_time' => '2024-07-15 14:00:00',
    'end_time' => '2024-07-15 16:00:00'
];

$googleCalendarLink = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=Test';

echo "=== Test 1: Generate email body with slot ===\n";
$emailBody = $method->invoke(null, 'John Doe', $testEvent, $testSlot, $googleCalendarLink);

if (strpos($emailBody, 'John Doe') !== false) {
    echo "✓ User name is included\n";
} else {
    echo "✗ User name is missing\n";
}

if (strpos($emailBody, 'Summer Festival 2024') !== false) {
    echo "✓ Event title is included\n";
} else {
    echo "✗ Event title is missing\n";
}

if (strpos($emailBody, 'Main Campus, Building H') !== false) {
    echo "✓ Location is included\n";
} else {
    echo "✗ Location is missing\n";
}

if (strpos($emailBody, 'Max Mustermann') !== false) {
    echo "✓ Contact person is included\n";
} else {
    echo "✗ Contact person is missing\n";
}

if (strpos($emailBody, '15.07.2024 14:00 - 16:00') !== false) {
    echo "✓ Slot time is included\n";
} else {
    echo "✗ Slot time is missing\n";
}

if (strpos($emailBody, 'calendar.google.com') !== false) {
    echo "✓ Google Calendar link is included\n";
} else {
    echo "✗ Google Calendar link is missing\n";
}

if (strpos($emailBody, '<!DOCTYPE html>') !== false && 
    strpos($emailBody, '</html>') !== false) {
    echo "✓ Email body is valid HTML\n";
} else {
    echo "✗ Email body is not valid HTML\n";
}

echo "\n=== Test 2: Generate email body without slot (full event) ===\n";
$emailBodyFull = $method->invoke(null, 'Jane Smith', $testEvent, null, $googleCalendarLink);

if (strpos($emailBodyFull, '15.07.2024 10:00 - 15.07.2024 18:00') !== false) {
    echo "✓ Full event time range is included\n";
} else {
    echo "✗ Full event time range is missing\n";
}

echo "\n=== Test 3: HTML structure validation ===\n";

// Check for proper HTML structure
$hasHeader = strpos($emailBody, '<div class="header">') !== false;
$hasContent = strpos($emailBody, '<div class="content">') !== false;
$hasFooter = strpos($emailBody, '<div class="footer">') !== false;

if ($hasHeader && $hasContent && $hasFooter) {
    echo "✓ Email has proper structure (header, content, footer)\n";
} else {
    echo "✗ Email structure is incomplete\n";
}

// Check for CSS styling
if (strpos($emailBody, '<style>') !== false) {
    echo "✓ Email includes CSS styling\n";
} else {
    echo "✗ Email lacks CSS styling\n";
}

// Check for proper XSS prevention
$eventWithXSS = $testEvent;
$eventWithXSS['title'] = '<script>alert("XSS")</script>Test Event';
$emailBodyXSS = $method->invoke(null, 'Test User', $eventWithXSS, $testSlot, $googleCalendarLink);

if (strpos($emailBodyXSS, '<script>') === false && 
    strpos($emailBodyXSS, '&lt;script&gt;') !== false) {
    echo "✓ XSS is properly escaped\n";
} else {
    echo "✗ XSS is not properly escaped\n";
}

echo "\n=== All tests completed ===\n";
