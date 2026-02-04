<?php
/**
 * Test Event Attendee Count and List
 * Tests the enhanced getById() method with attendee_count and attendees array
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Event.php';
require_once __DIR__ . '/../includes/models/User.php';

echo "=== Event Attendee Test Suite ===\n\n";

try {
    // Setup: Create test users if they don't exist
    echo "Setup: Preparing test users\n";
    $contentDb = Database::getContentDB();
    $userDb = Database::getUserDB();
    
    // Create test users with profiles
    $testUsers = [];
    for ($i = 1; $i <= 3; $i++) {
        $email = "testuser{$i}_" . time() . "@test.com";
        
        // Check if user exists by email
        $stmt = $userDb->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            $userId = $existingUser['id'];
        } else {
            // Create user
            $stmt = $userDb->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
            $passwordHash = password_hash('testpass', PASSWORD_DEFAULT);
            $stmt->execute([$email, $passwordHash, 'member']);
            $userId = $userDb->lastInsertId();
            
            // Create alumni profile with name
            $stmt = $userDb->prepare("INSERT INTO alumni_profiles (user_id, first_name, last_name) VALUES (?, ?, ?)");
            $stmt->execute([$userId, "Test{$i}", "User"]);
        }
        
        $testUsers[] = $userId;
    }
    echo "✓ Test users prepared: " . implode(', ', $testUsers) . "\n\n";
    
    // Test 1: Create Event
    echo "Test 1: Create Event\n";
    $eventData = [
        'title' => 'Test Attendee Event',
        'description' => 'Event to test attendee functionality',
        'location' => 'Test Location',
        'start_time' => date('Y-m-d H:i:s', strtotime('+7 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+7 days +2 hours')),
        'contact_person' => 'Test Manager',
        'status' => 'open',
        'is_external' => false,
        'needs_helpers' => false,
        'allowed_roles' => ['member', 'board', 'manager']
    ];
    
    $eventId = Event::create($eventData, $testUsers[0]);
    echo "✓ Event created with ID: $eventId\n\n";
    
    // Test 2: Check initial attendee_count (should be 0)
    echo "Test 2: Check Initial Attendee Count\n";
    $event = Event::getById($eventId);
    if (isset($event['attendee_count'])) {
        echo "✓ attendee_count field exists\n";
        echo "  Initial count: {$event['attendee_count']}\n";
        if ($event['attendee_count'] == 0) {
            echo "✓ Correct initial count (0)\n";
        } else {
            echo "✗ Expected 0, got {$event['attendee_count']}\n";
        }
    } else {
        echo "✗ attendee_count field missing\n";
    }
    
    if (isset($event['attendees'])) {
        echo "✓ attendees field exists\n";
        echo "  Initial attendees: " . (empty($event['attendees']) ? 'empty (correct)' : 'not empty (incorrect)') . "\n";
    } else {
        echo "✗ attendees field missing\n";
    }
    echo "\n";
    
    // Test 3: Add confirmed signups
    echo "Test 3: Add Confirmed Signups\n";
    foreach ($testUsers as $index => $userId) {
        $stmt = $contentDb->prepare("
            INSERT INTO event_signups (event_id, user_id, status)
            VALUES (?, ?, 'confirmed')
        ");
        $stmt->execute([$eventId, $userId]);
        echo "  Added user $userId as confirmed attendee\n";
    }
    echo "✓ Added 3 confirmed signups\n\n";
    
    // Test 4: Check updated attendee_count
    echo "Test 4: Check Updated Attendee Count\n";
    $event = Event::getById($eventId);
    if (isset($event['attendee_count'])) {
        echo "  Attendee count: {$event['attendee_count']}\n";
        if ($event['attendee_count'] == 3) {
            echo "✓ Correct count (3 confirmed attendees)\n";
        } else {
            echo "✗ Expected 3, got {$event['attendee_count']}\n";
        }
    } else {
        echo "✗ attendee_count field missing\n";
    }
    echo "\n";
    
    // Test 5: Check attendees array
    echo "Test 5: Check Attendees Array\n";
    if (isset($event['attendees']) && is_array($event['attendees'])) {
        echo "  Number of attendees in array: " . count($event['attendees']) . "\n";
        if (count($event['attendees']) == 3) {
            echo "✓ Correct number of attendees\n";
        } else {
            echo "✗ Expected 3 attendees, got " . count($event['attendees']) . "\n";
        }
        
        // Check structure
        echo "  Checking attendee data structure:\n";
        foreach ($event['attendees'] as $attendee) {
            if (isset($attendee['user_id']) && isset($attendee['first_name']) && isset($attendee['last_name'])) {
                echo "    ✓ User {$attendee['user_id']}: {$attendee['first_name']} {$attendee['last_name']}\n";
            } else {
                echo "    ✗ Missing required fields for attendee\n";
            }
        }
    } else {
        echo "✗ attendees field is not an array or missing\n";
    }
    echo "\n";
    
    // Test 6: Add waitlist signup (should not affect count)
    echo "Test 6: Add Waitlist Signup (Should Not Affect Count)\n";
    $email = "waitlist_" . time() . "@test.com";
    $stmt = $userDb->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $passwordHash = password_hash('testpass', PASSWORD_DEFAULT);
    $stmt->execute([$email, $passwordHash, 'member']);
    $waitlistUserId = $userDb->lastInsertId();
    
    $stmt = $contentDb->prepare("
        INSERT INTO event_signups (event_id, user_id, status)
        VALUES (?, ?, 'waitlist')
    ");
    $stmt->execute([$eventId, $waitlistUserId]);
    echo "  Added waitlist user\n";
    
    $event = Event::getById($eventId);
    echo "  Attendee count: {$event['attendee_count']}\n";
    if ($event['attendee_count'] == 3) {
        echo "✓ Count unchanged (waitlist not counted)\n";
    } else {
        echo "✗ Expected 3, got {$event['attendee_count']}\n";
    }
    
    if (count($event['attendees']) == 3) {
        echo "✓ Attendees array unchanged (waitlist not included)\n";
    } else {
        echo "✗ Expected 3 attendees, got " . count($event['attendees']) . "\n";
    }
    echo "\n";
    
    // Test 7: Cancel a signup (should decrease count)
    echo "Test 7: Cancel Signup (Should Decrease Count)\n";
    $stmt = $contentDb->prepare("
        UPDATE event_signups 
        SET status = 'cancelled' 
        WHERE event_id = ? AND user_id = ?
    ");
    $stmt->execute([$eventId, $testUsers[0]]);
    echo "  Cancelled signup for user {$testUsers[0]}\n";
    
    $event = Event::getById($eventId);
    echo "  Attendee count: {$event['attendee_count']}\n";
    if ($event['attendee_count'] == 2) {
        echo "✓ Count decreased correctly (2 confirmed attendees)\n";
    } else {
        echo "✗ Expected 2, got {$event['attendee_count']}\n";
    }
    
    if (count($event['attendees']) == 2) {
        echo "✓ Attendees array updated (cancelled user removed)\n";
    } else {
        echo "✗ Expected 2 attendees, got " . count($event['attendees']) . "\n";
    }
    echo "\n";
    
    // Cleanup
    echo "Cleanup: Removing test data\n";
    $stmt = $contentDb->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    
    foreach ($testUsers as $userId) {
        $stmt = $userDb->prepare("DELETE FROM alumni_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stmt = $userDb->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    }
    $stmt = $userDb->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$waitlistUserId]);
    
    echo "✓ Test data cleaned up\n\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
