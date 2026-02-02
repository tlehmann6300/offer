<?php
/**
 * Test script for Event status update functionality
 * Run with: php tests/test_event_status_update.php
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

echo "Testing Event Status Update Functionality...\n\n";

try {
    $db = Database::getContentDB();
    
    // Create test events
    echo "=== Setting up test events ===\n";
    
    // Test 1: Create a planned event that should become open
    $testEvent1 = [
        'title' => 'Test Event - Future Planned',
        'description' => 'This event should change from planned to open',
        'start_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
        'status' => 'planned',
        'needs_helpers' => false
    ];
    
    $stmt = $db->prepare("
        INSERT INTO events (title, description, start_time, end_time, status, needs_helpers)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $testEvent1['title'],
        $testEvent1['description'],
        $testEvent1['start_time'],
        $testEvent1['end_time'],
        $testEvent1['status'],
        $testEvent1['needs_helpers']
    ]);
    $testEventId1 = $db->lastInsertId();
    echo "✓ Created test event 1 (ID: {$testEventId1}) - planned, future\n";
    
    // Test 2: Create an event that has already passed
    $testEvent2 = [
        'title' => 'Test Event - Past Event',
        'description' => 'This event should change to past status',
        'start_time' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'status' => 'open',
        'needs_helpers' => false
    ];
    
    $stmt->execute([
        $testEvent2['title'],
        $testEvent2['description'],
        $testEvent2['start_time'],
        $testEvent2['end_time'],
        $testEvent2['status'],
        $testEvent2['needs_helpers']
    ]);
    $testEventId2 = $db->lastInsertId();
    echo "✓ Created test event 2 (ID: {$testEventId2}) - open, past\n";
    
    // Test 3: Create an event that is currently running
    $testEvent3 = [
        'title' => 'Test Event - Currently Running',
        'description' => 'This event should stay as is',
        'start_time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'status' => 'running',
        'needs_helpers' => false
    ];
    
    $stmt->execute([
        $testEvent3['title'],
        $testEvent3['description'],
        $testEvent3['start_time'],
        $testEvent3['end_time'],
        $testEvent3['status'],
        $testEvent3['needs_helpers']
    ]);
    $testEventId3 = $db->lastInsertId();
    echo "✓ Created test event 3 (ID: {$testEventId3}) - running, current\n";
    
    echo "\n=== Running status update ===\n";
    $updates = Event::updateEventStatuses();
    
    echo "Updates made:\n";
    echo "- Planned to Open: {$updates['planned_to_open']}\n";
    echo "- To Past: {$updates['to_past']}\n";
    
    echo "\n=== Verifying results ===\n";
    
    // Check event 1
    $stmt = $db->prepare("SELECT status FROM events WHERE id = ?");
    $stmt->execute([$testEventId1]);
    $event1Status = $stmt->fetchColumn();
    if ($event1Status === 'open') {
        echo "✓ Event 1 status changed to 'open'\n";
    } else {
        echo "✗ Event 1 status is '{$event1Status}' (expected 'open')\n";
    }
    
    // Check event 2
    $stmt->execute([$testEventId2]);
    $event2Status = $stmt->fetchColumn();
    if ($event2Status === 'past') {
        echo "✓ Event 2 status changed to 'past'\n";
    } else {
        echo "✗ Event 2 status is '{$event2Status}' (expected 'past')\n";
    }
    
    // Check event 3 (should remain as is or change to past if ended)
    $stmt->execute([$testEventId3]);
    $event3Status = $stmt->fetchColumn();
    if ($event3Status === 'running' || $event3Status === 'past') {
        echo "✓ Event 3 status is '{$event3Status}' (as expected)\n";
    } else {
        echo "✗ Event 3 status is '{$event3Status}' (unexpected)\n";
    }
    
    echo "\n=== Cleaning up test events ===\n";
    $stmt = $db->prepare("DELETE FROM events WHERE id IN (?, ?, ?)");
    $stmt->execute([$testEventId1, $testEventId2, $testEventId3]);
    echo "✓ Test events cleaned up\n";
    
    echo "\n=== All tests completed ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
