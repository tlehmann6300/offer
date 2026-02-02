<?php
/**
 * Unit Test for calculateStatus Logic
 * Tests the status calculation logic without database connection
 * Run with: php tests/test_calculate_status_unit.php
 */

echo "=== calculateStatus() Logic Unit Test ===\n\n";

// Simulate the calculateStatus function
function calculateStatus($data) {
    $now = time();
    
    // Parse timestamps
    $registrationStart = !empty($data['registration_start']) ? strtotime($data['registration_start']) : null;
    $registrationEnd = !empty($data['registration_end']) ? strtotime($data['registration_end']) : null;
    $startTime = strtotime($data['start_time']);
    $endTime = strtotime($data['end_time']);
    
    // Status logic based on timestamps
    // 1. If event has ended -> past
    if ($now > $endTime) {
        return 'past';
    }
    
    // 2. If event is running -> running
    if ($now >= $startTime && $now <= $endTime) {
        return 'running';
    }
    
    // 3. If registration dates are set, use them
    if ($registrationStart !== null && $registrationEnd !== null) {
        // Before registration starts -> planned
        if ($now < $registrationStart) {
            return 'planned';
        }
        
        // During registration period -> open
        if ($now >= $registrationStart && $now <= $registrationEnd) {
            return 'open';
        }
        
        // After registration ends but before event starts -> closed
        if ($now > $registrationEnd && $now < $startTime) {
            return 'closed';
        }
    } else {
        // No registration dates: if event hasn't started yet -> open
        if ($now < $startTime) {
            return 'open';
        }
    }
    
    // Default fallback
    return 'planned';
}

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Event with registration not yet started
echo "Test 1: Registration not yet started\n";
$event1 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+10 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+10 days +2 hours')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('+5 days'))
];
$status1 = calculateStatus($event1);
if ($status1 === 'planned') {
    echo "✓ PASS: Status is 'planned'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'planned', got '$status1'\n\n";
    $testsFailed++;
}

// Test 2: Event with open registration
echo "Test 2: Registration currently open\n";
$event2 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+10 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+10 days +3 hours')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('+5 days'))
];
$status2 = calculateStatus($event2);
if ($status2 === 'open') {
    echo "✓ PASS: Status is 'open'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'open', got '$status2'\n\n";
    $testsFailed++;
}

// Test 3: Event with closed registration
echo "Test 3: Registration closed, event upcoming\n";
$event3 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 days +2 hours')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-5 days')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('-1 day'))
];
$status3 = calculateStatus($event3);
if ($status3 === 'closed') {
    echo "✓ PASS: Status is 'closed'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'closed', got '$status3'\n\n";
    $testsFailed++;
}

// Test 4: Event currently running
echo "Test 4: Event currently running\n";
$event4 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-10 days')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('-1 day'))
];
$status4 = calculateStatus($event4);
if ($status4 === 'running') {
    echo "✓ PASS: Status is 'running'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'running', got '$status4'\n\n";
    $testsFailed++;
}

// Test 5: Past event
echo "Test 5: Event has ended\n";
$event5 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('-3 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('-2 days')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-10 days')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('-5 days'))
];
$status5 = calculateStatus($event5);
if ($status5 === 'past') {
    echo "✓ PASS: Status is 'past'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'past', got '$status5'\n\n";
    $testsFailed++;
}

// Test 6: Event without registration dates (future event)
echo "Test 6: No registration dates, future event\n";
$event6 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +1 hour'))
];
$status6 = calculateStatus($event6);
if ($status6 === 'open') {
    echo "✓ PASS: Status is 'open' (default for future events without registration dates)\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'open', got '$status6'\n\n";
    $testsFailed++;
}

// Test 7: Event at exact start time
echo "Test 7: Event starting right now\n";
$event7 = [
    'start_time' => date('Y-m-d H:i:s', time()),
    'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-5 days')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('-1 day'))
];
$status7 = calculateStatus($event7);
if ($status7 === 'running') {
    echo "✓ PASS: Status is 'running'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'running', got '$status7'\n\n";
    $testsFailed++;
}

// Test 8: Event at exact end time
echo "Test 8: Event ending right now\n";
$event8 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
    'end_time' => date('Y-m-d H:i:s', time()),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-5 days')),
    'registration_end' => date('Y-m-d H:i:s', strtotime('-3 days'))
];
$status8 = calculateStatus($event8);
if ($status8 === 'running') {
    echo "✓ PASS: Status is 'running' (at exact end time)\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'running', got '$status8'\n\n";
    $testsFailed++;
}

// Test 9: Registration ending right now
echo "Test 9: Registration ending right now\n";
$event9 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
    'registration_start' => date('Y-m-d H:i:s', strtotime('-3 days')),
    'registration_end' => date('Y-m-d H:i:s', time())
];
$status9 = calculateStatus($event9);
if ($status9 === 'open') {
    echo "✓ PASS: Status is 'open' (at exact registration end time)\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'open', got '$status9'\n\n";
    $testsFailed++;
}

// Test 10: Registration starting right now
echo "Test 10: Registration starting right now\n";
$event10 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
    'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
    'registration_start' => date('Y-m-d H:i:s', time()),
    'registration_end' => date('Y-m-d H:i:s', strtotime('+3 days'))
];
$status10 = calculateStatus($event10);
if ($status10 === 'open') {
    echo "✓ PASS: Status is 'open' (at exact registration start time)\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'open', got '$status10'\n\n";
    $testsFailed++;
}

// Summary
echo "=== Test Summary ===\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "✓ All tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed!\n";
    exit(1);
}
