<?php
/**
 * Unit Test for Event Validation Logic
 * Tests validation without database connection
 * Run with: php tests/test_event_validation_unit.php
 */

echo "=== Event Validation Logic Unit Test ===\n\n";

// Mock the validation function from Event.php
function validateEventData($data) {
    $timezone = new DateTimeZone('Europe/Berlin');
    
    // Validate that end_time > start_time
    if (!empty($data['start_time']) && !empty($data['end_time'])) {
        try {
            $startTime = new DateTime($data['start_time'], $timezone);
            $endTime = new DateTime($data['end_time'], $timezone);
            
            if ($endTime <= $startTime) {
                throw new Exception("Event end time must be after start time");
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'end time must be after') !== false) {
                throw $e;
            }
            throw new Exception("Invalid date format for start_time or end_time");
        } catch (Throwable $e) {
            // Catch DateMalformedStringException and other Throwables in PHP 8.3+
            throw new Exception("Invalid date format for start_time or end_time");
        }
    }
    
    // Validate that registration_end < end_time
    if (!empty($data['registration_end']) && !empty($data['end_time'])) {
        try {
            $registrationEnd = new DateTime($data['registration_end'], $timezone);
            $endTime = new DateTime($data['end_time'], $timezone);
            
            if ($registrationEnd >= $endTime) {
                throw new Exception("Registration end time must be before event end time");
            }
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Registration end time') !== false) {
                throw $e;
            }
            throw new Exception("Invalid date format for registration_end or end_time");
        } catch (Throwable $e) {
            // Catch DateMalformedStringException and other Throwables in PHP 8.3+
            throw new Exception("Invalid date format for registration_end or end_time");
        }
    }
    
    // Validate maps_link if provided
    if (!empty($data['maps_link'])) {
        $mapsLink = trim($data['maps_link']);
        if ($mapsLink !== '' && filter_var($mapsLink, FILTER_VALIDATE_URL) === false) {
            throw new Exception("Maps link must be a valid URL");
        }
    }
}

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Valid event data should pass
echo "Test 1: Valid event data\n";
try {
    $validData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'registration_start' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+4 days')),
        'maps_link' => 'https://maps.google.com/?q=Berlin'
    ];
    validateEventData($validData);
    echo "✓ PASS: Valid event data accepted\n\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "✗ FAIL: Valid data rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 2: end_time <= start_time should fail
echo "Test 2: Invalid - end_time <= start_time\n";
try {
    $invalidData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+4 days'))
    ];
    validateEventData($invalidData);
    echo "✗ FAIL: Should have thrown exception\n\n";
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

// Test 3: Equal start and end times should fail
echo "Test 3: Invalid - equal start and end times\n";
try {
    $sameTime = date('Y-m-d H:i:s', strtotime('+5 days'));
    $invalidData = [
        'start_time' => $sameTime,
        'end_time' => $sameTime
    ];
    validateEventData($invalidData);
    echo "✗ FAIL: Should have thrown exception\n\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'end time must be after') !== false) {
        echo "✓ PASS: Correctly rejected equal times\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Test 4: registration_end >= end_time should fail
echo "Test 4: Invalid - registration_end >= end_time\n";
try {
    $invalidData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'registration_end' => date('Y-m-d H:i:s', strtotime('+6 days'))
    ];
    validateEventData($invalidData);
    echo "✗ FAIL: Should have thrown exception\n\n";
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

// Test 5: Invalid maps_link should fail
echo "Test 5: Invalid maps_link format\n";
try {
    $invalidData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'maps_link' => 'not-a-valid-url'
    ];
    validateEventData($invalidData);
    echo "✗ FAIL: Should have thrown exception\n\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'valid URL') !== false) {
        echo "✓ PASS: Correctly rejected invalid URL\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Test 6: Valid maps_link should pass
echo "Test 6: Valid maps_link\n";
try {
    $validData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'maps_link' => 'https://www.google.com/maps'
    ];
    validateEventData($validData);
    echo "✓ PASS: Valid URL accepted\n\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "✗ FAIL: Valid URL rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 7: Empty maps_link should pass
echo "Test 7: Empty maps_link\n";
try {
    $validData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours')),
        'maps_link' => ''
    ];
    validateEventData($validData);
    echo "✓ PASS: Empty maps_link accepted\n\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "✗ FAIL: Empty maps_link rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 8: Null maps_link should pass
echo "Test 8: Null/missing maps_link\n";
try {
    $validData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours'))
    ];
    validateEventData($validData);
    echo "✓ PASS: Missing maps_link accepted\n\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "✗ FAIL: Missing maps_link rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Test 9: Invalid date format should fail gracefully
echo "Test 9: Invalid date format\n";
try {
    $invalidData = [
        'start_time' => 'not-a-date',
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days'))
    ];
    validateEventData($invalidData);
    echo "✗ FAIL: Should have thrown exception\n\n";
    $testsFailed++;
} catch (Throwable $e) {
    if (strpos($e->getMessage(), 'Invalid date format') !== false || 
        strpos($e->getMessage(), 'Failed to parse') !== false) {
        echo "✓ PASS: Invalid date format rejected\n";
        echo "  Error: {$e->getMessage()}\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong exception: {$e->getMessage()}\n\n";
        $testsFailed++;
    }
}

// Test 10: Only dates without registration dates
echo "Test 10: Valid without registration dates\n";
try {
    $validData = [
        'start_time' => date('Y-m-d H:i:s', strtotime('+5 days')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+5 days +2 hours'))
    ];
    validateEventData($validData);
    echo "✓ PASS: Valid data without registration dates\n\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "✗ FAIL: Valid data rejected: {$e->getMessage()}\n\n";
    $testsFailed++;
}

// Summary
echo "=== Test Summary ===\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Total Tests: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "✓ All validation tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed!\n";
    exit(1);
}
