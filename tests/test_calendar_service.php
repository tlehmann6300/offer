<?php
/**
 * Test script for CalendarService
 * Run with: php tests/test_calendar_service.php
 */

require_once __DIR__ . '/../src/CalendarService.php';

echo "Testing CalendarService...\n\n";

// Test data for event
$testEvent = [
    'id' => 123,
    'title' => 'Test Event - Summer Festival',
    'description' => 'This is a test event for the summer festival. We need helpers for setup and cleanup.',
    'location' => 'Main Campus, Building H',
    'start_time' => '2024-07-15 10:00:00',
    'end_time' => '2024-07-15 18:00:00',
    'contact_person' => 'Max Mustermann (max@example.com)'
];

// Test data for slot
$testSlot = [
    'id' => 456,
    'start_time' => '2024-07-15 14:00:00',
    'end_time' => '2024-07-15 16:00:00',
    'slot_title' => 'Aufbau Team'
];

echo "=== Test 1: Generate ICS for full event ===\n";
$icsFullEvent = CalendarService::generateICS($testEvent);
echo "Generated ICS:\n";
echo substr($icsFullEvent, 0, 500) . "...\n\n";

// Validate ICS structure
if (strpos($icsFullEvent, 'BEGIN:VCALENDAR') !== false &&
    strpos($icsFullEvent, 'BEGIN:VEVENT') !== false &&
    strpos($icsFullEvent, 'END:VEVENT') !== false &&
    strpos($icsFullEvent, 'END:VCALENDAR') !== false) {
    echo "✓ ICS structure is valid\n";
} else {
    echo "✗ ICS structure is invalid\n";
}

if (strpos($icsFullEvent, 'Test Event - Summer Festival') !== false) {
    echo "✓ Event title is included\n";
} else {
    echo "✗ Event title is missing\n";
}

echo "\n=== Test 2: Generate ICS for specific slot ===\n";
$icsSlot = CalendarService::generateICS($testEvent, $testSlot);
echo "Generated ICS:\n";
echo substr($icsSlot, 0, 500) . "...\n\n";

if (strpos($icsSlot, 'Aufbau Team') !== false) {
    echo "✓ Slot title is included\n";
} else {
    echo "✗ Slot title is missing\n";
}

echo "\n=== Test 3: Generate Google Calendar link for full event ===\n";
$googleLinkFull = CalendarService::generateGoogleCalendarLink($testEvent);
echo "Generated Link:\n";
echo $googleLinkFull . "\n\n";

if (strpos($googleLinkFull, 'calendar.google.com') !== false &&
    strpos($googleLinkFull, 'action=TEMPLATE') !== false) {
    echo "✓ Google Calendar link is valid\n";
} else {
    echo "✗ Google Calendar link is invalid\n";
}

echo "\n=== Test 4: Generate Google Calendar link for specific slot ===\n";
$googleLinkSlot = CalendarService::generateGoogleCalendarLink($testEvent, $testSlot);
echo "Generated Link:\n";
echo $googleLinkSlot . "\n\n";

if (strpos($googleLinkSlot, 'Aufbau+Team') !== false || 
    strpos($googleLinkSlot, 'Aufbau%20Team') !== false) {
    echo "✓ Slot title is in Google Calendar link\n";
} else {
    echo "✗ Slot title is missing from Google Calendar link\n";
}

echo "\n=== Test 5: Test special character escaping ===\n";
$eventWithSpecialChars = [
    'id' => 789,
    'title' => 'Event, with; special\\chars',
    'description' => "Multi-line\ndescription\rwith various line breaks\r\n",
    'location' => 'Location; with, commas',
    'start_time' => '2024-07-15 10:00:00',
    'end_time' => '2024-07-15 18:00:00',
    'contact_person' => 'Test Person'
];

$icsSpecial = CalendarService::generateICS($eventWithSpecialChars);
if (strpos($icsSpecial, 'Event\\, with\\; special') !== false) {
    echo "✓ Special characters are properly escaped\n";
} else {
    echo "✗ Special characters are not properly escaped\n";
}

echo "\n=== All tests completed ===\n";
