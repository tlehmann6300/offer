<?php
/**
 * Test Event Model Hardening
 * Tests timezone handling, validation, and error resilience
 * Run with: php tests/test_event_hardening.php
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

echo "=== Event Model Hardening Test Suite ===\n\n";

$testUserId = 1;
$testsPassed = 0;
$testsFailed = 0;

// Test 1: Validate end_time > start_time
echo "Test 1: Validate end_time > start_time\n";
try {
    $invalidEventData = [
        'title' => 'Invalid Event - End before Start',
        'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'needs_helpers' => false
    ];
    
    Event::create($invalidEventData, $testUserId);
    echo "✗ FAIL: Should have thrown exception for end_time <= start_time\n\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'end time must be after') !== false) {
        echo "✓ PASS: Correctly rejected end_time <= start_time\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Test 2: Validate registration_end < end_time
echo "Test 2: Validate registration_end < end_time\n";
try {
    $invalidEventData = [
        'title' => 'Invalid Event - Reg after End',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+6 days')),
        'needs_helpers' => false
    ];
    
    Event::create($invalidEventData, $testUserId);
    echo "✗ FAIL: Should have thrown exception for registration_end >= end_time\n\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Registration end time') !== false) {
        echo "✓ PASS: Correctly rejected registration_end >= end_time\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Test 3: Validate maps_link URL format
echo "Test 3: Validate maps_link URL format (invalid)\n";
try {
    $invalidEventData = [
        'title' => 'Invalid Event - Bad Maps Link',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'maps_link' => 'not-a-valid-url',
        'needs_helpers' => false
    ];
    
    Event::create($invalidEventData, $testUserId);
    echo "✗ FAIL: Should have thrown exception for invalid maps_link\n\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'valid URL') !== false) {
        echo "✓ PASS: Correctly rejected invalid maps_link\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Test 4: Valid maps_link should pass
echo "Test 4: Valid maps_link should be accepted\n";
try {
    $validEventData = [
        'title' => 'Valid Event with Maps Link',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'maps_link' => 'https://maps.google.com/?q=Berlin',
        'needs_helpers' => false
    ];
    
    $eventId = Event::create($validEventData, $testUserId);
    echo "✓ PASS: Valid maps_link accepted\n";
    echo "  Event ID: $eventId\n\n";
    $testsPassed++;
    
    // Clean up
    $db = Database::getContentDB();
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
} catch (Exception $e) {
    echo "✗ FAIL: Valid maps_link rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 5: Empty maps_link should pass
echo "Test 5: Empty maps_link should be accepted\n";
try {
    $validEventData = [
        'title' => 'Valid Event without Maps Link',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'maps_link' => '',
        'needs_helpers' => false
    ];
    
    $eventId = Event::create($validEventData, $testUserId);
    echo "✓ PASS: Empty maps_link accepted\n";
    echo "  Event ID: $eventId\n\n";
    $testsPassed++;
    
    // Clean up
    $db = Database::getContentDB();
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
} catch (Exception $e) {
    echo "✗ FAIL: Empty maps_link rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 6: Timezone handling - Create event and check status
echo "Test 6: Timezone handling with Berlin timezone\n";
try {
    $timezone = new DateTimeZone('Europe/Berlin');
    $now = new DateTime('now', $timezone);
    
    // Create event that should be 'open' (starts in 5 days, no registration dates)
    $futureStart = clone $now;
    $futureStart->modify('+5 days');
    $futureEnd = clone $futureStart;
    $futureEnd->modify('+2 hours');
    
    $validEventData = [
        'title' => 'Future Event - Timezone Test',
        'start_time' => $futureStart->format('Y-m-d H:i:s'),
        'end_time' => $futureEnd->format('Y-m-d H:i:s'),
        'needs_helpers' => false
    ];
    
    $eventId = Event::create($validEventData, $testUserId);
    $event = Event::getById($eventId);
    
    if ($event['status'] === 'open') {
        echo "✓ PASS: Event created with correct timezone-based status\n";
        echo "  Status: {$event['status']}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Expected status 'open', got '{$event['status']}'\n\n";
        $testsFailed++;
    }
    
    // Clean up
    $db = Database::getContentDB();
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
} catch (Exception $e) {
    echo "✗ FAIL: Error creating event with timezone: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 7: Valid event with all dates should pass
echo "Test 7: Create valid event with all validation passing\n";
try {
    $validEventData = [
        'title' => 'Fully Valid Event',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +3 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+4 days')),
        'maps_link' => 'https://www.google.com/maps',
        'needs_helpers' => false
    ];
    
    $eventId = Event::create($validEventData, $testUserId);
    echo "✓ PASS: Valid event created successfully\n";
    echo "  Event ID: $eventId\n\n";
    $testsPassed++;
    
    // Clean up
    $db = Database::getContentDB();
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
} catch (Exception $e) {
    echo "✗ FAIL: Valid event rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 8: Update event with invalid data should fail
echo "Test 8: Update event with invalid end_time should fail\n";
try {
    // First create a valid event
    $validEventData = [
        'title' => 'Event for Update Test',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'needs_helpers' => false
    ];
    
    $eventId = Event::create($validEventData, $testUserId);
    
    // Try to update with invalid end_time
    $invalidUpdateData = [
        'end_time' => date('Y-m-d H:i:s', strtotime('+4 days'))  // Before start_time
    ];
    
    Event::update($eventId, $invalidUpdateData, $testUserId);
    echo "✗ FAIL: Should have thrown exception for invalid update\n\n";
    $testsFailed++;
    
    // Clean up
    $db = Database::getContentDB();
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'end time must be after') !== false) {
        echo "✓ PASS: Correctly rejected invalid update\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
        
        // Clean up
        $db = Database::getContentDB();
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Summary
echo "=== Test Summary ===\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "✓ All hardening tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed!\n";
    exit(1);
}
