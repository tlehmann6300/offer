<?php
/**
 * Unit test for AuthHandler hasRole method
 * Tests that hasRole checks for exact role match (not hierarchical)
 * Run with: php tests/test_authhandler_hasrole.php
 */

echo "Testing AuthHandler::hasRole() Method...\n\n";

// Get absolute paths
$configPath = realpath(__DIR__ . '/../config/config.php');
$authHandlerPath = realpath(__DIR__ . '/../includes/handlers/AuthHandler.php');

if (!$configPath || !$authHandlerPath) {
    echo "❌ FAILED: Could not find required files\n";
    echo "Config path: $configPath\n";
    echo "AuthHandler path: $authHandlerPath\n";
    exit(1);
}

require_once $configPath;
require_once $authHandlerPath;

// Test 1: Test hasRole method exists
echo "=== Test 1: Verify hasRole method exists ===\n";
if (!method_exists('AuthHandler', 'hasRole')) {
    echo "❌ FAILED: hasRole method does not exist\n";
    exit(1);
}
echo "✓ PASSED: hasRole method exists\n\n";

// Test 2: Test hasRole returns false when not authenticated
echo "=== Test 2: Test hasRole returns false when not authenticated ===\n";
// Start a clean session
if (session_status() !== PHP_SESSION_NONE) {
    session_destroy();
}
session_start();
$_SESSION = []; // Clear session

$result = AuthHandler::hasRole('admin');
if ($result === false) {
    echo "✓ PASSED: hasRole returns false when not authenticated\n\n";
} else {
    echo "❌ FAILED: hasRole should return false when not authenticated\n";
    exit(1);
}

// Test 3: Test hasRole returns false when authenticated but different role
echo "=== Test 3: Test hasRole with different role ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'manager';

$result = AuthHandler::hasRole('admin');
if ($result === false) {
    echo "✓ PASSED: hasRole('admin') returns false when user role is 'manager'\n\n";
} else {
    echo "❌ FAILED: hasRole should return false for different role\n";
    exit(1);
}

// Test 4: Test hasRole returns true when role matches exactly
echo "=== Test 4: Test hasRole with exact role match ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'admin';

$result = AuthHandler::hasRole('admin');
if ($result === true) {
    echo "✓ PASSED: hasRole('admin') returns true when user role is 'admin'\n\n";
} else {
    echo "❌ FAILED: hasRole should return true for exact role match\n";
    exit(1);
}

// Test 5: Test hasRole for 'board' role
echo "=== Test 5: Test hasRole with 'board' role ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'board';

$result = AuthHandler::hasRole('board');
if ($result === true) {
    echo "✓ PASSED: hasRole('board') returns true when user role is 'board'\n\n";
} else {
    echo "❌ FAILED: hasRole should return true for exact role match\n";
    exit(1);
}

// Test 6: Test that hasRole is NOT hierarchical (manager should not have admin role)
echo "=== Test 6: Test hasRole is not hierarchical ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'admin';

$resultBoard = AuthHandler::hasRole('board');
if ($resultBoard === false) {
    echo "✓ PASSED: hasRole is not hierarchical - admin does not have 'board' role\n\n";
} else {
    echo "❌ FAILED: hasRole should not be hierarchical\n";
    exit(1);
}

// Clean up
session_destroy();

echo "===========================================\n";
echo "All tests passed! ✓\n";
echo "===========================================\n";
