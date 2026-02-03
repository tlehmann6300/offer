<?php
/**
 * Unit Test for calculateStatus with Timezone and Error Resilience
 * Tests the improved calculateStatus logic with Berlin timezone and error handling
 * Run with: php tests/test_calculate_status_timezone.php
 */

echo "=== calculateStatus() Timezone & Error Resilience Test ===\n\n";

// Simulate the improved calculateStatus function with timezone handling
function calculateStatus($data) {
    try {
        // Use Berlin timezone for consistent time handling
        $timezone = new DateTimeZone('Europe/Berlin');
        $now = new DateTime('now', $timezone);
        
        // Parse timestamps relative to Berlin timezone
        $registrationStart = null;
        $registrationEnd = null;
        $startTime = null;
        $endTime = null;
        
        if (!empty($data['registration_start'])) {
            $registrationStart = new DateTime($data['registration_start'], $timezone);
        }
        if (!empty($data['registration_end'])) {
            $registrationEnd = new DateTime($data['registration_end'], $timezone);
        }
        if (!empty($data['start_time'])) {
            $startTime = new DateTime($data['start_time'], $timezone);
        }
        if (!empty($data['end_time'])) {
            $endTime = new DateTime($data['end_time'], $timezone);
        }
        
        // Validate that we have the required dates
        if ($startTime === null || $endTime === null) {
            return 'planned';
        }
        
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
    } catch (Throwable $e) {
        // Error resilience: Log error and fall back to 'planned'
        error_log("calculateStatus failed: " . $e->getMessage());
        return 'planned';
    }
}

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Timezone handling - future event
echo "Test 1: Future event with Berlin timezone\n";
$timezone = new DateTimeZone('Europe/Berlin');
$now = new DateTime('now', $timezone);
$futureStart = clone $now;
$futureStart->modify('+5 days');
$futureEnd = clone $futureStart;
$futureEnd->modify('+2 hours');

$event1 = [
    'start_time' => $futureStart->format('Y-m-d H:i:s'),
    'end_time' => $futureEnd->format('Y-m-d H:i:s')
];
$status1 = calculateStatus($event1);
if ($status1 === 'open') {
    echo "✓ PASS: Future event status is 'open'\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'open', got '$status1'\n\n";
    $testsFailed++;
}

// Test 2: Error resilience - invalid date format
echo "Test 2: Error resilience with invalid date\n";
$event2 = [
    'start_time' => 'invalid-date',
    'end_time' => date('Y-m-d H:i:s', strtotime('+5 days'))
];
$status2 = calculateStatus($event2);
if ($status2 === 'planned') {
    echo "✓ PASS: Falls back to 'planned' on error\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'planned' fallback, got '$status2'\n\n";
    $testsFailed++;
}

// Test 3: Error resilience - missing required dates
echo "Test 3: Missing start_time or end_time\n";
$event3 = [
    'start_time' => date('Y-m-d H:i:s', strtotime('+5 days'))
    // end_time is missing
];
$status3 = calculateStatus($event3);
if ($status3 === 'planned') {
    echo "✓ PASS: Falls back to 'planned' when dates missing\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'planned' fallback, got '$status3'\n\n";
    $testsFailed++;
}

// Test 4: Event currently running (timezone-aware)
echo "Test 4: Event currently running (timezone-aware)\n";
$now = new DateTime('now', $timezone);
$startNow = clone $now;
$startNow->modify('-30 minutes');
$endNow = clone $now;
$endNow->modify('+2 hours');

$event4 = [
    'start_time' => $startNow->format('Y-m-d H:i:s'),
    'end_time' => $endNow->format('Y-m-d H:i:s')
];
$status4 = calculateStatus($event4);
if ($status4 === 'running') {
    echo "✓ PASS: Running event detected correctly\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'running', got '$status4'\n\n";
    $testsFailed++;
}

// Test 5: Past event (timezone-aware)
echo "Test 5: Past event (timezone-aware)\n";
$pastStart = clone $now;
$pastStart->modify('-3 days');
$pastEnd = clone $now;
$pastEnd->modify('-2 days');

$event5 = [
    'start_time' => $pastStart->format('Y-m-d H:i:s'),
    'end_time' => $pastEnd->format('Y-m-d H:i:s')
];
$status5 = calculateStatus($event5);
if ($status5 === 'past') {
    echo "✓ PASS: Past event detected correctly\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'past', got '$status5'\n\n";
    $testsFailed++;
}

// Test 6: Registration period (timezone-aware)
echo "Test 6: During registration period (timezone-aware)\n";
$regStart = clone $now;
$regStart->modify('-1 day');
$regEnd = clone $now;
$regEnd->modify('+3 days');
$eventStart = clone $now;
$eventStart->modify('+5 days');
$eventEnd = clone $eventStart;
$eventEnd->modify('+2 hours');

$event6 = [
    'start_time' => $eventStart->format('Y-m-d H:i:s'),
    'end_time' => $eventEnd->format('Y-m-d H:i:s'),
    'registration_start' => $regStart->format('Y-m-d H:i:s'),
    'registration_end' => $regEnd->format('Y-m-d H:i:s')
];
$status6 = calculateStatus($event6);
if ($status6 === 'open') {
    echo "✓ PASS: Open registration detected correctly\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'open', got '$status6'\n\n";
    $testsFailed++;
}

// Test 7: Registration closed (timezone-aware)
echo "Test 7: Registration closed, event upcoming (timezone-aware)\n";
$regStart2 = clone $now;
$regStart2->modify('-5 days');
$regEnd2 = clone $now;
$regEnd2->modify('-1 day');
$eventStart2 = clone $now;
$eventStart2->modify('+2 days');
$eventEnd2 = clone $eventStart2;
$eventEnd2->modify('+2 hours');

$event7 = [
    'start_time' => $eventStart2->format('Y-m-d H:i:s'),
    'end_time' => $eventEnd2->format('Y-m-d H:i:s'),
    'registration_start' => $regStart2->format('Y-m-d H:i:s'),
    'registration_end' => $regEnd2->format('Y-m-d H:i:s')
];
$status7 = calculateStatus($event7);
if ($status7 === 'closed') {
    echo "✓ PASS: Closed registration detected correctly\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'closed', got '$status7'\n\n";
    $testsFailed++;
}

// Test 8: Registration not yet started (timezone-aware)
echo "Test 8: Registration not yet started (timezone-aware)\n";
$regStart3 = clone $now;
$regStart3->modify('+1 day');
$regEnd3 = clone $now;
$regEnd3->modify('+5 days');
$eventStart3 = clone $now;
$eventStart3->modify('+10 days');
$eventEnd3 = clone $eventStart3;
$eventEnd3->modify('+2 hours');

$event8 = [
    'start_time' => $eventStart3->format('Y-m-d H:i:s'),
    'end_time' => $eventEnd3->format('Y-m-d H:i:s'),
    'registration_start' => $regStart3->format('Y-m-d H:i:s'),
    'registration_end' => $regEnd3->format('Y-m-d H:i:s')
];
$status8 = calculateStatus($event8);
if ($status8 === 'planned') {
    echo "✓ PASS: Planned status detected correctly\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Expected 'planned', got '$status8'\n\n";
    $testsFailed++;
}

// Summary
echo "=== Test Summary ===\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "✓ All timezone & resilience tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed!\n";
    exit(1);
}
