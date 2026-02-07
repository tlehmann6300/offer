<?php
/**
 * Test: MailService send() method with attachment support
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Testing MailService send() method with attachments\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Check if send() method exists
echo "Test 1: Checking if send() method exists...\n";
if (method_exists('MailService', 'send')) {
    echo "✓ PASS: send() method exists\n";
} else {
    echo "✗ FAIL: send() method does not exist\n";
    exit(1);
}

// Test 2: Check method signature
echo "\nTest 2: Checking method signature...\n";
$reflection = new ReflectionMethod('MailService', 'send');
$parameters = $reflection->getParameters();
echo "Parameters: ";
foreach ($parameters as $param) {
    echo $param->getName();
    if ($param->isOptional()) {
        echo " (optional)";
    }
    echo ", ";
}
echo "\n";

if (count($parameters) === 4) {
    echo "✓ PASS: Method has 4 parameters (to, subject, body, attachments)\n";
} else {
    echo "✗ FAIL: Method should have 4 parameters, has " . count($parameters) . "\n";
    exit(1);
}

// Test 2b: Verify parameter names
echo "\nTest 2b: Verifying parameter names...\n";
$paramNames = array_map(function($p) { return $p->getName(); }, $parameters);
$expectedNames = ['to', 'subject', 'body', 'attachments'];
if ($paramNames === $expectedNames) {
    echo "✓ PASS: Parameter names are correct (to, subject, body, attachments)\n";
} else {
    echo "✗ FAIL: Parameter names do not match. Expected: " . implode(', ', $expectedNames) . ", Got: " . implode(', ', $paramNames) . "\n";
    exit(1);
}

// Test 3: Check if attachments parameter is optional
echo "\nTest 3: Checking if attachments parameter is optional...\n";
$lastParam = end($parameters);
if ($lastParam->isOptional()) {
    echo "✓ PASS: attachments parameter is optional\n";
} else {
    echo "✗ FAIL: attachments parameter should be optional\n";
    exit(1);
}

// Test 4: Check if method is public and static
echo "\nTest 4: Checking method visibility and static...\n";
if ($reflection->isPublic() && $reflection->isStatic()) {
    echo "✓ PASS: Method is public and static\n";
} else {
    echo "✗ FAIL: Method should be public and static\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "All tests passed!\n";
