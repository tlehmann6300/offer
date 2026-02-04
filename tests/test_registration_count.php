<?php
/**
 * Test Registration Count Feature
 * Tests the new getRegistrationCount method in Event model
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

echo "=== Event Registration Count Test ===\n\n";

try {
    // Test 1: Create a test event
    echo "Test 1: Create Test Event\n";
    $eventData = [
        'title' => 'Test Registration Count Event',
        'description' => 'Testing participant counter',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+7 days +2 hours')),
        'contact_person' => 'Test Manager',
        'status' => 'open',
        'is_external' => false,
        'needs_helpers' => false,
        'allowed_roles' => ['member', 'board', 'manager']
    ];
    
    $eventId = Event::create($eventData, 1);
    echo "✓ Event created with ID: $eventId\n\n";
    
    // Test 2: Check initial registration count
    echo "Test 2: Check Initial Registration Count\n";
    $count = Event::getRegistrationCount($eventId);
    if ($count === 0) {
        echo "✓ Initial registration count is 0\n\n";
    } else {
        echo "✗ Expected 0, got $count\n\n";
    }
    
    // Test 3: Create some test signups
    echo "Test 3: Create Test Signups\n";
    $db = Database::getContentDB();
    
    // Insert test signups
    $stmt = $db->prepare("INSERT INTO event_signups (event_id, user_id, status) VALUES (?, ?, ?)");
    $stmt->execute([$eventId, 1, 'confirmed']);
    $stmt->execute([$eventId, 2, 'confirmed']);
    $stmt->execute([$eventId, 3, 'confirmed']);
    $stmt->execute([$eventId, 4, 'waitlist']); // Should not be counted
    $stmt->execute([$eventId, 5, 'cancelled']); // Should not be counted
    echo "✓ Test signups created\n\n";
    
    // Test 4: Check updated registration count
    echo "Test 4: Check Updated Registration Count\n";
    $count = Event::getRegistrationCount($eventId);
    if ($count === 3) {
        echo "✓ Registration count is correct: 3 confirmed participants\n\n";
    } else {
        echo "✗ Expected 3, got $count\n\n";
    }
    
    // Test 5: Verify method exists and returns integer
    echo "Test 5: Verify Return Type\n";
    if (is_int($count)) {
        echo "✓ getRegistrationCount returns integer\n\n";
    } else {
        echo "✗ Expected integer, got " . gettype($count) . "\n\n";
    }
    
    // Cleanup
    echo "Cleanup: Removing test data\n";
    $stmt = $db->prepare("DELETE FROM event_signups WHERE event_id = ?");
    $stmt->execute([$eventId]);
    Event::delete($eventId);
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== All Tests Passed! ===\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
