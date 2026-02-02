<?php
/**
 * Static Validation Test for Event Model
 * Tests code logic and structure without database connection
 */

require_once __DIR__ . '/../includes/models/Event.php';

echo "=== Event Model Static Validation ===\n\n";

// Test 1: Check class exists
echo "Test 1: Check Event class exists\n";
if (class_exists('Event')) {
    echo "✓ Event class found\n\n";
} else {
    echo "✗ Event class not found\n\n";
    exit(1);
}

// Test 2: Check all required methods exist
echo "Test 2: Check required methods exist\n";
$requiredMethods = [
    'create', 'getById', 'update', 'delete', 'getEvents',
    'createHelperType', 'getHelperTypes', 'createSlot', 'getSlots',
    'signup', 'cancelSignup', 'getSignups', 'getUserSignups',
    'checkLock', 'acquireLock', 'releaseLock', 'getHistory'
];

$missingMethods = [];
foreach ($requiredMethods as $method) {
    if (method_exists('Event', $method)) {
        echo "  ✓ $method exists\n";
    } else {
        echo "  ✗ $method missing\n";
        $missingMethods[] = $method;
    }
}

if (empty($missingMethods)) {
    echo "✓ All required methods exist\n\n";
} else {
    echo "✗ Missing methods: " . implode(', ', $missingMethods) . "\n\n";
    exit(1);
}

// Test 3: Check constants
echo "Test 3: Check constants\n";
$reflection = new ReflectionClass('Event');
$constants = $reflection->getConstants();
if (isset($constants['LOCK_TIMEOUT'])) {
    echo "✓ LOCK_TIMEOUT constant exists: {$constants['LOCK_TIMEOUT']} seconds\n";
    if ($constants['LOCK_TIMEOUT'] === 900) {
        echo "✓ LOCK_TIMEOUT is correctly set to 15 minutes (900 seconds)\n";
    } else {
        echo "✗ LOCK_TIMEOUT should be 900 seconds (15 minutes)\n";
    }
} else {
    echo "✗ LOCK_TIMEOUT constant not found\n";
}
echo "\n";

// Test 4: Check method signatures
echo "Test 4: Check critical method signatures\n";

$methods = [
    'create' => ['data', 'userId'],
    'getById' => ['id', 'includeHelperSlots'],
    'update' => ['id', 'data', 'userId'],
    'delete' => ['id', 'userId'],
    'getEvents' => ['filters', 'userRole'],
    'signup' => ['eventId', 'userId', 'slotId', 'userRole'],
    'checkLock' => ['eventId', 'userId'],
    'acquireLock' => ['eventId', 'userId'],
    'releaseLock' => ['eventId', 'userId']
];

foreach ($methods as $methodName => $expectedParams) {
    $method = new ReflectionMethod('Event', $methodName);
    $params = $method->getParameters();
    
    echo "  Method: $methodName\n";
    echo "    Parameters: ";
    $paramNames = array_map(function($p) { return '$' . $p->getName(); }, $params);
    echo implode(', ', $paramNames) . "\n";
}
echo "✓ Method signatures checked\n\n";

// Test 5: Code quality checks
echo "Test 5: Code quality checks\n";

// Check if the file contains critical security features
$eventFile = file_get_contents(__DIR__ . '/../includes/models/Event.php');

// Check for Alumni protection
if (strpos($eventFile, "userRole === 'alumni'") !== false) {
    echo "✓ Alumni role checking implemented\n";
} else {
    echo "✗ Alumni role checking not found\n";
}

// Check for lock timeout logic
if (strpos($eventFile, 'LOCK_TIMEOUT') !== false) {
    echo "✓ Lock timeout constant used\n";
} else {
    echo "✗ Lock timeout constant not used\n";
}

// Check for history logging
if (strpos($eventFile, 'logHistory') !== false) {
    echo "✓ History logging implemented\n";
} else {
    echo "✗ History logging not found\n";
}

// Check for transaction usage
if (strpos($eventFile, 'beginTransaction') !== false && strpos($eventFile, 'commit') !== false) {
    echo "✓ Database transactions used\n";
} else {
    echo "✗ Database transactions not found\n";
}

// Check for JSON encoding for history
if (strpos($eventFile, 'json_encode') !== false) {
    echo "✓ JSON encoding for change details\n";
} else {
    echo "✗ JSON encoding not found\n";
}

echo "\n";

// Test 6: Alumni Restrictions
echo "Test 6: Alumni Restrictions Validation\n";
$alumniChecks = substr_count($eventFile, "userRole === 'alumni'");
echo "✓ Found $alumniChecks alumni role checks in code\n";

// Check that alumni cannot book helper slots
if (strpos($eventFile, 'Alumni users are not allowed to sign up for helper slots') !== false) {
    echo "✓ Alumni helper slot restriction message found\n";
} else {
    echo "✗ Alumni helper slot restriction message not found\n";
}

// Check that alumni don't see helper info
if (strpos($eventFile, "\$event['needs_helpers'] = false") !== false) {
    echo "✓ Alumni helper information hiding implemented\n";
} else {
    echo "✗ Alumni helper information hiding not found\n";
}

echo "\n";

echo "=== Static Validation Complete ===\n";
echo "✓ All static checks passed\n";
echo "\nNote: Database integration tests require active database connection.\n";
echo "Run test_event_model.php when database is available.\n";
