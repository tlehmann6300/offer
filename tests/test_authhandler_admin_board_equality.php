<?php
/**
 * Unit test for AuthHandler isAdmin, requireAdmin and updated hasRole methods
 * Tests that board role has equal privileges to admin
 * Run with: php tests/test_authhandler_admin_board_equality.php
 */

echo "Testing AuthHandler Admin/Board Equality...\n\n";

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

// Test 1: Test isAdmin method exists
echo "=== Test 1: Verify isAdmin method exists ===\n";
if (!method_exists('AuthHandler', 'isAdmin')) {
    echo "❌ FAILED: isAdmin method does not exist\n";
    exit(1);
}
echo "✓ PASSED: isAdmin method exists\n\n";

// Test 2: Test requireAdmin method exists
echo "=== Test 2: Verify requireAdmin method exists ===\n";
if (!method_exists('AuthHandler', 'requireAdmin')) {
    echo "❌ FAILED: requireAdmin method does not exist\n";
    exit(1);
}
echo "✓ PASSED: requireAdmin method exists\n\n";

// Test 3: Test isAdmin returns false when not authenticated
echo "=== Test 3: Test isAdmin returns false when not authenticated ===\n";
// Start a clean session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
session_start();
$_SESSION = []; // Clear session

$result = AuthHandler::isAdmin();
if ($result === false) {
    echo "✓ PASSED: isAdmin returns false when not authenticated\n\n";
} else {
    echo "❌ FAILED: isAdmin should return false when not authenticated\n";
    exit(1);
}

// Test 4: Test isAdmin returns true for admin role
echo "=== Test 4: Test isAdmin returns true for admin role ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'admin';

$result = AuthHandler::isAdmin();
if ($result === true) {
    echo "✓ PASSED: isAdmin returns true for 'admin' role\n\n";
} else {
    echo "❌ FAILED: isAdmin should return true for admin role\n";
    exit(1);
}

// Test 5: Test isAdmin returns true for board role (CRITICAL)
echo "=== Test 5: Test isAdmin returns true for board role ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'board';

$result = AuthHandler::isAdmin();
if ($result === true) {
    echo "✓ PASSED: isAdmin returns true for 'board' role (board = admin privileges)\n\n";
} else {
    echo "❌ FAILED: isAdmin should return true for board role\n";
    exit(1);
}

// Test 6: Test isAdmin returns false for other roles
echo "=== Test 6: Test isAdmin returns false for non-admin roles ===\n";
$testRoles = ['member', 'manager', 'alumni', 'candidate'];
foreach ($testRoles as $role) {
    $_SESSION['user_role'] = $role;
    $result = AuthHandler::isAdmin();
    if ($result === false) {
        echo "✓ PASSED: isAdmin returns false for '$role' role\n";
    } else {
        echo "❌ FAILED: isAdmin should return false for '$role' role\n";
        exit(1);
    }
}
echo "\n";

// Test 7: Test hasRole('admin') returns true for board role (NEW BEHAVIOR)
echo "=== Test 7: Test hasRole('admin') returns true for board role ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'board';

$result = AuthHandler::hasRole('admin');
if ($result === true) {
    echo "✓ PASSED: hasRole('admin') returns true for 'board' role (board = admin privileges)\n\n";
} else {
    echo "❌ FAILED: hasRole('admin') should return true for board role\n";
    exit(1);
}

// Test 8: Test hasRole('admin') returns true for admin role
echo "=== Test 8: Test hasRole('admin') returns true for admin role ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'admin';

$result = AuthHandler::hasRole('admin');
if ($result === true) {
    echo "✓ PASSED: hasRole('admin') returns true for 'admin' role\n\n";
} else {
    echo "❌ FAILED: hasRole('admin') should return true for admin role\n";
    exit(1);
}

// Test 9: Test hasRole('admin') returns false for other roles
echo "=== Test 9: Test hasRole('admin') returns false for non-admin/board roles ===\n";
$testRoles = ['member', 'manager', 'alumni', 'candidate'];
foreach ($testRoles as $role) {
    $_SESSION['user_role'] = $role;
    $result = AuthHandler::hasRole('admin');
    if ($result === false) {
        echo "✓ PASSED: hasRole('admin') returns false for '$role' role\n";
    } else {
        echo "❌ FAILED: hasRole('admin') should return false for '$role' role\n";
        exit(1);
    }
}
echo "\n";

// Test 10: Test hasRole still works for exact role matches (non-admin)
echo "=== Test 10: Test hasRole for exact role matches (non-admin) ===\n";
$_SESSION['authenticated'] = true;
$_SESSION['user_role'] = 'member';

$result = AuthHandler::hasRole('member');
if ($result === true) {
    echo "✓ PASSED: hasRole('member') returns true for 'member' role\n\n";
} else {
    echo "❌ FAILED: hasRole should return true for exact role match\n";
    exit(1);
}

// Clean up
session_destroy();

echo "===========================================\n";
echo "All tests passed! ✓\n";
echo "Board role now has equal privileges to Admin role\n";
echo "===========================================\n";
