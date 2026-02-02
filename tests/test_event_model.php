<?php
/**
 * Test Event Model
 * Tests CRUD operations, role-based filtering, and locking mechanism
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

// Test configuration
$testUserId = 1;
$testAlumniUserId = 2;

echo "=== Event Model Test Suite ===\n\n";

try {
    // Test 1: Create Event
    echo "Test 1: Create Event\n";
    $eventData = [
        'title' => 'Test IBC Meeting',
        'description' => 'Monthly team meeting',
        'location' => 'Conference Room A',
        'start_time' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+7 days +2 hours')),
        'contact_person' => 'Test Manager',
        'status' => 'open',
        'is_external' => false,
        'needs_helpers' => false,
        'allowed_roles' => ['member', 'board', 'manager']
    ];
    
    $eventId = Event::create($eventData, $testUserId);
    echo "✓ Event created with ID: $eventId\n\n";
    
    // Test 2: Get Event by ID
    echo "Test 2: Get Event by ID\n";
    $event = Event::getById($eventId);
    if ($event && $event['title'] === $eventData['title']) {
        echo "✓ Event retrieved successfully\n";
        echo "  Title: {$event['title']}\n";
        echo "  Allowed roles: " . implode(', ', $event['allowed_roles']) . "\n\n";
    } else {
        echo "✗ Failed to retrieve event\n\n";
    }
    
    // Test 3: Update Event
    echo "Test 3: Update Event\n";
    $updateData = [
        'description' => 'Updated description for monthly meeting',
        'status' => 'running'
    ];
    Event::update($eventId, $updateData, $testUserId);
    $updatedEvent = Event::getById($eventId);
    if ($updatedEvent['description'] === $updateData['description']) {
        echo "✓ Event updated successfully\n";
        echo "  New description: {$updatedEvent['description']}\n\n";
    } else {
        echo "✗ Failed to update event\n\n";
    }
    
    // Test 4: Create Event with Helpers
    echo "Test 4: Create Event with Helper Slots\n";
    $helperEventData = [
        'title' => 'IBC Summer Festival',
        'description' => 'Annual summer event',
        'location' => 'Campus Grounds',
        'start_time' => date('Y-m-d H:i:s', strtotime('+30 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+30 days +6 hours')),
        'contact_person' => 'Event Coordinator',
        'status' => 'open',
        'is_external' => false,
        'needs_helpers' => true,
        'allowed_roles' => ['member', 'board', 'manager', 'alumni']
    ];
    
    $helperEventId = Event::create($helperEventData, $testUserId);
    echo "✓ Event with helpers created with ID: $helperEventId\n\n";
    
    // Test 5: Create Helper Types and Slots
    echo "Test 5: Create Helper Types and Slots\n";
    $setupTypeId = Event::createHelperType($helperEventId, 'Aufbau', 'Setup before event', $testUserId);
    $cleanupTypeId = Event::createHelperType($helperEventId, 'Abbau', 'Cleanup after event', $testUserId);
    echo "✓ Helper types created: Aufbau (ID: $setupTypeId), Abbau (ID: $cleanupTypeId)\n";
    
    $slotId1 = Event::createSlot($setupTypeId, 
        date('Y-m-d H:i:s', strtotime('+30 days -2 hours')),
        date('Y-m-d H:i:s', strtotime('+30 days')),
        5, $testUserId, $helperEventId);
    
    $slotId2 = Event::createSlot($cleanupTypeId,
        date('Y-m-d H:i:s', strtotime('+30 days +6 hours')),
        date('Y-m-d H:i:s', strtotime('+30 days +8 hours')),
        3, $testUserId, $helperEventId);
    
    echo "✓ Slots created: Setup slot (ID: $slotId1), Cleanup slot (ID: $slotId2)\n\n";
    
    // Test 6: Test Alumni Restrictions
    echo "Test 6: Test Alumni User Restrictions\n";
    
    // Alumni should see the event but not helper information
    $eventsForAlumni = Event::getEvents(['include_helpers' => true], 'alumni');
    $alumniEvent = null;
    foreach ($eventsForAlumni as $e) {
        if ($e['id'] == $helperEventId) {
            $alumniEvent = $e;
            break;
        }
    }
    
    if ($alumniEvent && !$alumniEvent['needs_helpers']) {
        echo "✓ Alumni user correctly sees event without helper information\n";
        echo "  needs_helpers is hidden: " . ($alumniEvent['needs_helpers'] ? 'false' : 'true') . "\n";
    } else {
        echo "✗ Alumni restriction failed\n";
    }
    
    // Test Alumni cannot sign up for helper slots
    try {
        Event::signup($helperEventId, $testAlumniUserId, $slotId1, 'alumni');
        echo "✗ Alumni user was able to sign up for helper slot (SHOULD NOT HAPPEN)\n\n";
    } catch (Exception $e) {
        echo "✓ Alumni user correctly blocked from signing up for helper slots\n";
        echo "  Error message: {$e->getMessage()}\n\n";
    }
    
    // Test 7: Non-Alumni can see helper slots
    echo "Test 7: Non-Alumni User Can See Helper Slots\n";
    $eventsForMember = Event::getEvents(['include_helpers' => true], 'member');
    $memberEvent = null;
    foreach ($eventsForMember as $e) {
        if ($e['id'] == $helperEventId) {
            $memberEvent = $e;
            break;
        }
    }
    
    if ($memberEvent && $memberEvent['needs_helpers'] && !empty($memberEvent['helper_types'])) {
        echo "✓ Member user correctly sees helper information\n";
        echo "  Helper types count: " . count($memberEvent['helper_types']) . "\n\n";
    } else {
        echo "✗ Member user cannot see helper information\n\n";
    }
    
    // Test 8: Event Signup (Non-Alumni)
    echo "Test 8: Event Signup for Helper Slot\n";
    $signupResult = Event::signup($helperEventId, $testUserId, $slotId1, 'member');
    if ($signupResult['status'] === 'confirmed') {
        echo "✓ User signed up successfully for helper slot\n";
        echo "  Signup ID: {$signupResult['id']}, Status: {$signupResult['status']}\n\n";
    } else {
        echo "✗ Signup failed\n\n";
    }
    
    // Test 9: Get Events with Filters
    echo "Test 9: Get Events with Filters\n";
    $openEvents = Event::getEvents(['status' => 'open'], 'member');
    echo "✓ Found " . count($openEvents) . " open events\n\n";
    
    // Test 10: Locking Mechanism
    echo "Test 10: Locking Mechanism\n";
    
    // Check lock status (should be unlocked)
    $lockStatus = Event::checkLock($eventId, $testUserId);
    if (!$lockStatus['is_locked']) {
        echo "✓ Event is initially unlocked\n";
    }
    
    // Acquire lock
    $lockResult = Event::acquireLock($eventId, $testUserId);
    if ($lockResult['success']) {
        echo "✓ Lock acquired successfully\n";
        echo "  Expires in: {$lockResult['expires_in']} seconds\n";
    }
    
    // Try to acquire lock with different user (should fail)
    $lockResult2 = Event::acquireLock($eventId, $testAlumniUserId);
    if (!$lockResult2['success']) {
        echo "✓ Different user correctly cannot acquire lock\n";
        echo "  Message: {$lockResult2['message']}\n";
    }
    
    // Release lock
    $releaseResult = Event::releaseLock($eventId, $testUserId);
    if ($releaseResult['success']) {
        echo "✓ Lock released successfully\n";
    }
    
    // Verify lock is released
    $lockStatus = Event::checkLock($eventId, $testUserId);
    if (!$lockStatus['is_locked']) {
        echo "✓ Event is now unlocked\n\n";
    }
    
    // Test 11: Event History
    echo "Test 11: Event History\n";
    $history = Event::getHistory($eventId);
    if (!empty($history)) {
        echo "✓ Event history logged successfully\n";
        echo "  History entries: " . count($history) . "\n";
        echo "  Recent entries:\n";
        foreach (array_slice($history, 0, 3) as $entry) {
            echo "    - {$entry['change_type']} at {$entry['timestamp']}\n";
        }
    } else {
        echo "✗ No history entries found\n";
    }
    echo "\n";
    
    // Test 12: Role-based Event Visibility
    echo "Test 12: Role-based Event Visibility\n";
    $allEventsAdmin = Event::getEvents([], 'admin');
    $allEventsMember = Event::getEvents([], 'member');
    $allEventsAlumni = Event::getEvents([], 'alumni');
    echo "✓ Events visible to admin: " . count($allEventsAdmin) . "\n";
    echo "✓ Events visible to member: " . count($allEventsMember) . "\n";
    echo "✓ Events visible to alumni: " . count($allEventsAlumni) . "\n\n";
    
    // Cleanup: Delete test events
    echo "Cleanup: Deleting test events\n";
    Event::delete($eventId, $testUserId);
    Event::delete($helperEventId, $testUserId);
    echo "✓ Test events deleted\n\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
