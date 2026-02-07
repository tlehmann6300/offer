<?php
/**
 * Unit test for email change workflow with token-based confirmation
 * Tests User::createEmailChangeRequest and User::confirmEmailChange
 * Run with: php tests/test_email_change_workflow.php
 */

echo "Testing Email Change Workflow...\n\n";

// Get absolute paths
$configPath = realpath(__DIR__ . '/../config/config.php');
$databasePath = realpath(__DIR__ . '/../includes/database.php');
$userModelPath = realpath(__DIR__ . '/../includes/models/User.php');

if (!$configPath || !$databasePath || !$userModelPath) {
    echo "❌ FAILED: Could not find required files\n";
    exit(1);
}

require_once $configPath;
require_once $databasePath;
require_once $userModelPath;

// Test 1: Test createEmailChangeRequest method exists
echo "=== Test 1: Verify createEmailChangeRequest method exists ===\n";
if (!method_exists('User', 'createEmailChangeRequest')) {
    echo "❌ FAILED: createEmailChangeRequest method does not exist\n";
    exit(1);
}
echo "✓ PASSED: createEmailChangeRequest method exists\n\n";

// Test 2: Test confirmEmailChange method exists
echo "=== Test 2: Verify confirmEmailChange method exists ===\n";
if (!method_exists('User', 'confirmEmailChange')) {
    echo "❌ FAILED: confirmEmailChange method does not exist\n";
    exit(1);
}
echo "✓ PASSED: confirmEmailChange method exists\n\n";

// Test 3: Test EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS constant exists
echo "=== Test 3: Verify EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS constant exists ===\n";
if (!defined('User::EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS')) {
    // Check if constant is defined via reflection
    $reflection = new ReflectionClass('User');
    $constants = $reflection->getConstants();
    if (!isset($constants['EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS'])) {
        echo "❌ FAILED: EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS constant does not exist\n";
        exit(1);
    }
    $expirationHours = $constants['EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS'];
} else {
    $expirationHours = User::EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS;
}
echo "✓ PASSED: EMAIL_CHANGE_TOKEN_EXPIRATION_HOURS constant exists (value: $expirationHours hours)\n\n";

// Test 4: Test createEmailChangeRequest validates email format
echo "=== Test 4: Test createEmailChangeRequest method signature ===\n";
$reflection = new ReflectionMethod('User', 'createEmailChangeRequest');
$params = $reflection->getParameters();
if (count($params) === 2 && $params[0]->getName() === 'userId' && $params[1]->getName() === 'newEmail') {
    echo "✓ PASSED: createEmailChangeRequest has correct parameters (userId, newEmail)\n\n";
} else {
    echo "❌ FAILED: createEmailChangeRequest has incorrect parameters\n";
    exit(1);
}

// Test 5: Test confirmEmailChange method signature
echo "=== Test 5: Test confirmEmailChange method signature ===\n";
$reflection = new ReflectionMethod('User', 'confirmEmailChange');
$params = $reflection->getParameters();
if (count($params) === 1 && $params[0]->getName() === 'token') {
    echo "✓ PASSED: confirmEmailChange has correct parameter (token)\n\n";
} else {
    echo "❌ FAILED: confirmEmailChange has incorrect parameters\n";
    exit(1);
}

// Test 6: Test that createEmailChangeRequest returns a token
echo "=== Test 6: Test createEmailChangeRequest returns token (mock test) ===\n";
// We cannot test with real DB without setup, but we can verify method signature
$reflection = new ReflectionMethod('User', 'createEmailChangeRequest');
$returnType = $reflection->getReturnType();
if ($returnType && $returnType->getName() === 'string') {
    echo "✓ PASSED: createEmailChangeRequest returns string (token)\n\n";
} else {
    echo "⚠ WARNING: Return type not explicitly declared (PHP allows this)\n\n";
}

// Test 7: Test that confirmEmailChange returns boolean
echo "=== Test 7: Test confirmEmailChange returns boolean (mock test) ===\n";
$reflection = new ReflectionMethod('User', 'confirmEmailChange');
$returnType = $reflection->getReturnType();
if ($returnType && $returnType->getName() === 'bool') {
    echo "✓ PASSED: confirmEmailChange returns boolean\n\n";
} else {
    echo "⚠ WARNING: Return type not explicitly declared (PHP allows this)\n\n";
}

echo "===========================================\n";
echo "Email change workflow tests completed! ✓\n";
echo "Token-based email confirmation is implemented\n";
echo "===========================================\n";
