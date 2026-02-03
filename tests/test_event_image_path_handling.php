<?php
/**
 * Test Event Image Path Handling
 * Tests that empty or missing image_path in $data array is handled correctly
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';

// Test configuration
$testUserId = 1;

echo "=== Event Image Path Handling Test Suite ===\n\n";

try {
    // Test 1: Create Event without image_path in $data (should use NULL)
    echo "Test 1: Create Event without image_path in \$data\n";
    $eventData = [
        'title' => 'Test Event Without Image',
        'description' => 'Testing event creation without image',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+7 days +2 hours')),
        'contact_person' => 'Test Manager',
        'is_external' => false,
        'needs_helpers' => false
    ];
    
    $eventId1 = Event::create($eventData, $testUserId);
    $event1 = Event::getById($eventId1);
    if ($event1['image_path'] === null) {
        echo "✓ Event created with NULL image_path (ID: $eventId1)\n\n";
    } else {
        echo "✗ Failed: image_path should be NULL but is: " . var_export($event1['image_path'], true) . "\n\n";
    }
    
    // Test 2: Create Event with empty image_path in $data (should use NULL)
    echo "Test 2: Create Event with empty image_path in \$data\n";
    $eventData2 = [
        'title' => 'Test Event With Empty Image',
        'description' => 'Testing event creation with empty image path',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+8 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+8 days +2 hours')),
        'contact_person' => 'Test Manager',
        'is_external' => false,
        'needs_helpers' => false,
        'image_path' => ''  // Empty string
    ];
    
    $eventId2 = Event::create($eventData2, $testUserId);
    $event2 = Event::getById($eventId2);
    if ($event2['image_path'] === null) {
        echo "✓ Event created with NULL image_path despite empty string in \$data (ID: $eventId2)\n\n";
    } else {
        echo "✗ Failed: image_path should be NULL but is: " . var_export($event2['image_path'], true) . "\n\n";
    }
    
    // Test 3: Create Event with valid image_path in $data
    echo "Test 3: Create Event with valid image_path in \$data\n";
    $eventData3 = [
        'title' => 'Test Event With Valid Image Path',
        'description' => 'Testing event creation with valid image path',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+9 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+9 days +2 hours')),
        'contact_person' => 'Test Manager',
        'is_external' => false,
        'needs_helpers' => false,
        'image_path' => '/uploads/events/test_image.jpg'
    ];
    
    $eventId3 = Event::create($eventData3, $testUserId);
    $event3 = Event::getById($eventId3);
    if ($event3['image_path'] === '/uploads/events/test_image.jpg') {
        echo "✓ Event created with correct image_path (ID: $eventId3)\n\n";
    } else {
        echo "✗ Failed: image_path should be '/uploads/events/test_image.jpg' but is: " . var_export($event3['image_path'], true) . "\n\n";
    }
    
    // Test 4: Update Event without changing image_path (should preserve old value)
    echo "Test 4: Update Event without providing image_path\n";
    $updateData = [
        'description' => 'Updated description'
    ];
    Event::update($eventId3, $updateData, $testUserId);
    $updatedEvent3 = Event::getById($eventId3);
    if ($updatedEvent3['image_path'] === '/uploads/events/test_image.jpg') {
        echo "✓ Event updated successfully, image_path preserved\n\n";
    } else {
        echo "✗ Failed: image_path should be preserved but is: " . var_export($updatedEvent3['image_path'], true) . "\n\n";
    }
    
    // Test 5: Update Event with empty image_path (should preserve old value)
    echo "Test 5: Update Event with empty image_path in \$data\n";
    $updateData2 = [
        'description' => 'Updated description again',
        'image_path' => ''  // Empty string should not overwrite existing value
    ];
    Event::update($eventId3, $updateData2, $testUserId);
    $updatedEvent3Again = Event::getById($eventId3);
    if ($updatedEvent3Again['image_path'] === '/uploads/events/test_image.jpg') {
        echo "✓ Event updated successfully, empty image_path ignored and old value preserved\n\n";
    } else {
        echo "✗ Failed: image_path should be preserved but is: " . var_export($updatedEvent3Again['image_path'], true) . "\n\n";
    }
    
    // Test 6: Update Event with new image_path (should update value)
    echo "Test 6: Update Event with new image_path in \$data\n";
    $updateData3 = [
        'description' => 'Updated with new image',
        'image_path' => '/uploads/events/new_test_image.jpg'
    ];
    Event::update($eventId3, $updateData3, $testUserId);
    $updatedEvent3Final = Event::getById($eventId3);
    if ($updatedEvent3Final['image_path'] === '/uploads/events/new_test_image.jpg') {
        echo "✓ Event updated successfully with new image_path\n\n";
    } else {
        echo "✗ Failed: image_path should be '/uploads/events/new_test_image.jpg' but is: " . var_export($updatedEvent3Final['image_path'], true) . "\n\n";
    }
    
    // Test 7: Update Event to remove image_path (set to NULL explicitly)
    echo "Test 7: Update Event to remove image_path (set to NULL)\n";
    $updateData4 = [
        'description' => 'Image removed',
        'image_path' => null
    ];
    Event::update($eventId3, $updateData4, $testUserId);
    $updatedEventNoImage = Event::getById($eventId3);
    if ($updatedEventNoImage['image_path'] === null) {
        echo "✓ Event updated successfully, image_path set to NULL\n\n";
    } else {
        echo "✗ Failed: image_path should be NULL but is: " . var_export($updatedEventNoImage['image_path'], true) . "\n\n";
    }
    
    // Cleanup - delete test events
    echo "Cleanup: Deleting test events\n";
    Event::delete($eventId1, $testUserId);
    Event::delete($eventId2, $testUserId);
    Event::delete($eventId3, $testUserId);
    echo "✓ Test events deleted\n\n";
    
    echo "=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "✗ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
