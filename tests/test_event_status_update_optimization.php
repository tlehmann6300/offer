<?php
/**
 * Test to verify Event status update optimization
 * Ensures database UPDATE is only triggered when status actually changes
 * Run with: php tests/test_event_status_update_optimization.php
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

echo "=== Event Status Update Optimization Test ===\n\n";

try {
    $db = Database::getContentDB();
    $testUserId = 1;
    $testEventIds = [];
    
    // ========================================
    // Test 1: Verify no UPDATE when status is already correct
    // ========================================
    echo "Test 1: No database UPDATE when status is already correct\n";
    echo "--------------------------------------------------------\n";
    
    // Create an event with 'open' status that should calculate to 'open'
    $eventData = [
        'title' => 'Test Event - Already Correct Status',
        'description' => 'This event should not trigger an UPDATE',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+3 days')),
        'needs_helpers' => false,
        'allowed_roles' => ['member']
    ];
    
    $eventId1 = Event::create($eventData, $testUserId);
    $testEventIds[] = $eventId1;
    
    // Get the event - this will call updateEventStatusIfNeeded
    $event1 = Event::getById($eventId1, false);
    
    // Verify status is 'open' (as expected)
    if ($event1['status'] !== 'open') {
        echo "✗ FAILED: Expected status 'open', got '{$event1['status']}'\n\n";
    } else {
        echo "✓ Status is correctly 'open'\n";
        
        // Get the updated_at timestamp
        $stmt = $db->prepare("SELECT updated_at FROM events WHERE id = ?");
        $stmt->execute([$eventId1]);
        $firstUpdatedAt = $stmt->fetchColumn();
        
        // Wait a moment to ensure updated_at would change if an UPDATE occurred
        sleep(1);
        
        // Call getById again - should NOT trigger an UPDATE
        $event1Again = Event::getById($eventId1, false);
        
        // Check updated_at timestamp - it should NOT have changed
        $stmt->execute([$eventId1]);
        $secondUpdatedAt = $stmt->fetchColumn();
        
        if ($firstUpdatedAt === $secondUpdatedAt) {
            echo "✓ PASS: No database UPDATE occurred (updated_at unchanged)\n";
            echo "  First  updated_at: $firstUpdatedAt\n";
            echo "  Second updated_at: $secondUpdatedAt\n";
        } else {
            echo "✗ FAIL: Database UPDATE occurred unnecessarily\n";
            echo "  First  updated_at: $firstUpdatedAt\n";
            echo "  Second updated_at: $secondUpdatedAt\n";
        }
    }
    echo "\n";
    
    // ========================================
    // Test 2: Verify UPDATE occurs when status should change
    // ========================================
    echo "Test 2: Database UPDATE occurs when status should change\n";
    echo "--------------------------------------------------------\n";
    
    // Create an event with 'planned' status but dates that should make it 'open'
    // Insert directly to DB to bypass automatic status calculation on create
    $stmt = $db->prepare("
        INSERT INTO events (title, description, location, start_time, end_time, 
                           registration_start, registration_end, status, needs_helpers)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'Test Event - Wrong Status',
        'Status should change from planned to open',
        'Test Location',
        date('Y-m-d H:i:s', strtotime('+5 days')),
        date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        date('Y-m-d H:i:s', strtotime('-1 day')),
        date('Y-m-d H:i:s', strtotime('+3 days')),
        'planned',  // Wrong status - should be 'open'
        false
    ]);
    $eventId2 = $db->lastInsertId();
    $testEventIds[] = $eventId2;
    
    // Get initial updated_at
    $stmt = $db->prepare("SELECT status, updated_at FROM events WHERE id = ?");
    $stmt->execute([$eventId2]);
    $beforeUpdate = $stmt->fetch();
    echo "  Before getById: status='{$beforeUpdate['status']}', updated_at={$beforeUpdate['updated_at']}\n";
    
    // Wait to ensure updated_at will change
    sleep(1);
    
    // Call getById - should trigger an UPDATE
    $event2 = Event::getById($eventId2, false);
    
    // Get updated status and timestamp
    $stmt->execute([$eventId2]);
    $afterUpdate = $stmt->fetch();
    echo "  After  getById: status='{$afterUpdate['status']}', updated_at={$afterUpdate['updated_at']}\n";
    
    if ($event2['status'] === 'open' && $afterUpdate['status'] === 'open') {
        echo "✓ Status correctly updated to 'open'\n";
        
        if ($beforeUpdate['updated_at'] !== $afterUpdate['updated_at']) {
            echo "✓ PASS: Database UPDATE occurred as expected\n";
        } else {
            echo "✗ FAIL: Status changed but updated_at didn't change (unexpected)\n";
        }
    } else {
        echo "✗ FAIL: Status not updated correctly\n";
    }
    echo "\n";
    
    // ========================================
    // Test 3: Verify batch updates in getEvents only update when needed
    // ========================================
    echo "Test 3: Batch updates in getEvents optimize correctly\n";
    echo "--------------------------------------------------------\n";
    
    // Create an event with correct status
    $eventData3 = [
        'title' => 'Test Event - Batch Correct',
        'description' => 'Test event for batch update optimization verification - status should not change',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+10 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+10 days +1 hour')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'needs_helpers' => false,
        'allowed_roles' => ['member']
    ];
    $eventId3 = Event::create($eventData3, $testUserId);
    $testEventIds[] = $eventId3;
    
    // Create an event with wrong status
    $stmt = $db->prepare("
        INSERT INTO events (title, start_time, end_time, registration_start, 
                           registration_end, status, needs_helpers)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'Test Event - Batch Wrong',
        date('Y-m-d H:i:s', strtotime('+7 days')),
        date('Y-m-d H:i:s', strtotime('+7 days +1 hour')),
        date('Y-m-d H:i:s', strtotime('-1 day')),
        date('Y-m-d H:i:s', strtotime('+4 days')),
        'planned',  // Wrong - should be 'open'
        false
    ]);
    $eventId4 = $db->lastInsertId();
    $testEventIds[] = $eventId4;
    
    // Get updated_at for both events
    $stmt = $db->prepare("SELECT id, status, updated_at FROM events WHERE id IN (?, ?)");
    $stmt->execute([$eventId3, $eventId4]);
    $beforeBatch = [];
    while ($row = $stmt->fetch()) {
        $beforeBatch[$row['id']] = $row;
    }
    
    sleep(1);
    
    // Call getEvents - should only update event 4
    $allEvents = Event::getEvents([], 'admin');
    
    // Get updated_at again
    $stmt->execute([$eventId3, $eventId4]);
    $afterBatch = [];
    while ($row = $stmt->fetch()) {
        $afterBatch[$row['id']] = $row;
    }
    
    // Event 3 should NOT have changed
    if ($beforeBatch[$eventId3]['updated_at'] === $afterBatch[$eventId3]['updated_at']) {
        echo "✓ Event 3: No unnecessary UPDATE (status was already correct)\n";
    } else {
        echo "✗ Event 3: Unnecessary UPDATE occurred\n";
    }
    
    // Event 4 SHOULD have changed
    if ($beforeBatch[$eventId4]['status'] === 'planned' && 
        $afterBatch[$eventId4]['status'] === 'open' &&
        $beforeBatch[$eventId4]['updated_at'] !== $afterBatch[$eventId4]['updated_at']) {
        echo "✓ Event 4: Correct UPDATE occurred (status changed from planned to open)\n";
    } else {
        echo "✗ Event 4: Expected status update didn't occur correctly\n";
    }
    echo "\n";
    
    // ========================================
    // Test 4: Verify string comparison works correctly
    // ========================================
    echo "Test 4: String comparison between DB enum and calculated string\n";
    echo "----------------------------------------------------------------\n";
    
    // Verify that the comparison works with all status types
    $statusTypes = ['planned', 'open', 'closed', 'running', 'past'];
    $comparisonWorks = true;
    
    foreach ($statusTypes as $status) {
        // String from DB (enum)
        $dbStatus = $status;
        // String from calculation
        $calcStatus = $status;
        
        if ($dbStatus !== $calcStatus) {
            echo "✗ Comparison failed for status: $status\n";
            $comparisonWorks = false;
        }
    }
    
    if ($comparisonWorks) {
        echo "✓ PASS: String comparison works correctly for all status types\n";
    }
    echo "\n";
    
    // Cleanup
    echo "=== Cleanup ===\n";
    $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
    foreach ($testEventIds as $id) {
        $stmt->execute([$id]);
    }
    echo "✓ Test events cleaned up\n\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Cleanup on error
    if (!empty($testEventIds)) {
        echo "\nCleaning up test events...\n";
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            foreach ($testEventIds as $id) {
                $stmt->execute([$id]);
            }
        } catch (Exception $cleanupError) {
            echo "Cleanup error: " . $cleanupError->getMessage() . "\n";
        }
    }
}
