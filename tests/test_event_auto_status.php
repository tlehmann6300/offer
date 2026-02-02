<?php
/**
 * Test Automatic Event Status Calculation
 * Tests the new automatic status calculation based on registration dates and event times
 * Run with: php tests/test_event_auto_status.php
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

echo "=== Automatic Event Status Calculation Test Suite ===\n\n";

$testUserId = 1;
$testEventIds = [];

try {
    $db = Database::getContentDB();
    
    // Test 1: Event with registration not yet started (status should be 'planned')
    echo "Test 1: Event with registration not yet started\n";
    $eventData1 = [
        'title' => 'Test Event - Planned',
        'description' => 'Registration has not started yet',
        'location' => 'Room H-1.88',
        'maps_link' => 'https://maps.google.com/example1',
        'start_time' => date('Y-m-d H:i:s', strtotime('+10 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+10 days +2 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'contact_person' => 'Test Manager',
        'is_external' => false,
        'needs_helpers' => false,
        'allowed_roles' => ['member', 'board']
    ];
    
    $eventId1 = Event::create($eventData1, $testUserId);
    $testEventIds[] = $eventId1;
    $event1 = Event::getById($eventId1);
    
    if ($event1['status'] === 'planned') {
        echo "✓ Event status correctly set to 'planned'\n";
    } else {
        echo "✗ Event status is '{$event1['status']}' (expected 'planned')\n";
    }
    echo "  Registration start: {$event1['registration_start']}\n";
    echo "  Event start: {$event1['start_time']}\n\n";
    
    // Test 2: Event with open registration (status should be 'open')
    echo "Test 2: Event with open registration\n";
    $eventData2 = [
        'title' => 'Test Event - Open',
        'description' => 'Registration is currently open',
        'location' => 'Campus Grounds',
        'maps_link' => 'https://maps.google.com/example2',
        'start_time' => date('Y-m-d H:i:s', strtotime('+10 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+10 days +3 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'contact_person' => 'Event Coordinator',
        'is_external' => false,
        'needs_helpers' => true,
        'allowed_roles' => ['member', 'board', 'manager']
    ];
    
    $eventId2 = Event::create($eventData2, $testUserId);
    $testEventIds[] = $eventId2;
    $event2 = Event::getById($eventId2);
    
    if ($event2['status'] === 'open') {
        echo "✓ Event status correctly set to 'open'\n";
    } else {
        echo "✗ Event status is '{$event2['status']}' (expected 'open')\n";
    }
    echo "  Registration end: {$event2['registration_end']}\n";
    echo "  Event start: {$event2['start_time']}\n\n";
    
    // Test 3: Event with closed registration (status should be 'closed')
    echo "Test 3: Event with closed registration\n";
    $eventData3 = [
        'title' => 'Test Event - Closed',
        'description' => 'Registration has ended, waiting for event to start',
        'location' => 'Auditorium',
        'maps_link' => 'https://maps.google.com/example3',
        'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+2 days +2 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'contact_person' => 'Event Manager',
        'is_external' => false,
        'needs_helpers' => false,
        'allowed_roles' => ['member']
    ];
    
    $eventId3 = Event::create($eventData3, $testUserId);
    $testEventIds[] = $eventId3;
    $event3 = Event::getById($eventId3);
    
    if ($event3['status'] === 'closed') {
        echo "✓ Event status correctly set to 'closed'\n";
    } else {
        echo "✗ Event status is '{$event3['status']}' (expected 'closed')\n";
    }
    echo "  Registration ended: {$event3['registration_end']}\n";
    echo "  Event starts in: " . round((strtotime($event3['start_time']) - time()) / 3600, 1) . " hours\n\n";
    
    // Test 4: Event currently running (status should be 'running')
    echo "Test 4: Event currently running\n";
    $eventData4 = [
        'title' => 'Test Event - Running',
        'description' => 'Event is happening right now',
        'location' => 'Main Hall',
        'maps_link' => 'https://maps.google.com/example4',
        'start_time' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('-10 days')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'contact_person' => 'Event Host',
        'is_external' => false,
        'needs_helpers' => false
    ];
    
    $eventId4 = Event::create($eventData4, $testUserId);
    $testEventIds[] = $eventId4;
    $event4 = Event::getById($eventId4);
    
    if ($event4['status'] === 'running') {
        echo "✓ Event status correctly set to 'running'\n";
    } else {
        echo "✗ Event status is '{$event4['status']}' (expected 'running')\n";
    }
    echo "  Event started: {$event4['start_time']}\n";
    echo "  Event ends: {$event4['end_time']}\n\n";
    
    // Test 5: Past event (status should be 'past')
    echo "Test 5: Past event\n";
    $eventData5 = [
        'title' => 'Test Event - Past',
        'description' => 'This event has already ended',
        'location' => 'Conference Room',
        'maps_link' => 'https://maps.google.com/example5',
        'start_time' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('-10 days')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'contact_person' => 'Past Event Manager',
        'is_external' => false,
        'needs_helpers' => false
    ];
    
    $eventId5 = Event::create($eventData5, $testUserId);
    $testEventIds[] = $eventId5;
    $event5 = Event::getById($eventId5);
    
    if ($event5['status'] === 'past') {
        echo "✓ Event status correctly set to 'past'\n";
    } else {
        echo "✗ Event status is '{$event5['status']}' (expected 'past')\n";
    }
    echo "  Event ended: {$event5['end_time']}\n\n";
    
    // Test 6: Event without registration dates (should default to 'open' if future)
    echo "Test 6: Event without registration dates (future event)\n";
    $eventData6 = [
        'title' => 'Test Event - No Registration Dates',
        'description' => 'Event without specific registration period',
        'location' => 'Meeting Room',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +1 hour')),
        'contact_person' => 'Simple Event Host',
        'is_external' => true,
        'external_link' => 'https://example.com/event',
        'needs_helpers' => false
    ];
    
    $eventId6 = Event::create($eventData6, $testUserId);
    $testEventIds[] = $eventId6;
    $event6 = Event::getById($eventId6);
    
    if ($event6['status'] === 'open') {
        echo "✓ Event status correctly set to 'open' (no registration dates)\n";
    } else {
        echo "✗ Event status is '{$event6['status']}' (expected 'open')\n";
    }
    echo "  No registration dates set\n";
    echo "  Event start: {$event6['start_time']}\n\n";
    
    // Test 7: Update event and verify status recalculation
    echo "Test 7: Update event and verify status recalculation\n";
    $updateData = [
        'registration_end' => date('Y-m-d H:i:s', strtotime('-1 hour')), // Move registration end to past
        'start_time' => $event2['start_time'],
        'end_time' => $event2['end_time']
    ];
    
    Event::update($eventId2, $updateData, $testUserId);
    $updatedEvent2 = Event::getById($eventId2);
    
    if ($updatedEvent2['status'] === 'closed') {
        echo "✓ Event status updated to 'closed' after registration end moved to past\n";
    } else {
        echo "✗ Event status is '{$updatedEvent2['status']}' (expected 'closed')\n";
    }
    echo "  Old status: open, New status: {$updatedEvent2['status']}\n\n";
    
    // Test 8: Verify maps_link is saved and retrieved
    echo "Test 8: Verify maps_link handling\n";
    if (!empty($event1['maps_link']) && $event1['maps_link'] === $eventData1['maps_link']) {
        echo "✓ maps_link correctly saved and retrieved\n";
        echo "  maps_link: {$event1['maps_link']}\n\n";
    } else {
        echo "✗ maps_link not properly handled\n\n";
    }
    
    // Test 9: Test lazy status update in getEvents()
    echo "Test 9: Test lazy status update in getEvents()\n";
    $allEvents = Event::getEvents([], 'admin');
    echo "✓ Retrieved " . count($allEvents) . " events with lazy status updates\n";
    
    // Count events by status
    $statusCounts = [];
    foreach ($allEvents as $event) {
        $status = $event['status'];
        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    }
    echo "  Status distribution: ";
    foreach ($statusCounts as $status => $count) {
        echo "$status: $count, ";
    }
    echo "\n\n";
    
    // Test 10: Verify status cannot be manually set via update
    echo "Test 10: Verify status cannot be manually overridden\n";
    $manualStatusUpdate = [
        'status' => 'running', // Try to manually set to running (should be ignored)
        'title' => 'Updated Title'
    ];
    
    Event::update($eventId1, $manualStatusUpdate, $testUserId);
    $updatedEvent1 = Event::getById($eventId1);
    
    if ($updatedEvent1['status'] === 'planned') {
        echo "✓ Manual status override correctly ignored (status remains 'planned')\n";
        echo "  Attempted to set: running, Actual status: {$updatedEvent1['status']}\n";
    } else {
        echo "✗ Manual status override was applied (status: {$updatedEvent1['status']})\n";
    }
    echo "\n";
    
    // Cleanup
    echo "=== Cleanup ===\n";
    foreach ($testEventIds as $eventId) {
        Event::delete($eventId, $testUserId);
    }
    echo "✓ All test events deleted\n\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Attempt cleanup on error
    if (!empty($testEventIds)) {
        echo "\nCleaning up test events...\n";
        foreach ($testEventIds as $eventId) {
            try {
                Event::delete($eventId, $testUserId);
            } catch (Exception $cleanupError) {
                // Ignore cleanup errors
            }
        }
    }
}
