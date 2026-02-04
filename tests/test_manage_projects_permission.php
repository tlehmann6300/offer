<?php
/**
 * Test manage_projects Permission
 * Tests that the new manage_projects permission works correctly
 */

require_once __DIR__ . '/../src/Auth.php';

echo "=== manage_projects Permission Test Suite ===\n\n";

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$testsPassed = 0;
$testsFailed = 0;

// Helper function to test permission
function testPermission($role, $permission, $expectedResult, &$testsPassed, &$testsFailed) {
    $_SESSION['user_role'] = $role;
    $_SESSION['authenticated'] = true;
    $_SESSION['last_activity'] = time();
    
    $hasPermission = Auth::hasPermission($permission);
    $passed = ($hasPermission === $expectedResult);
    
    echo "Testing: $role has $permission permission\n";
    echo "Expected: " . ($expectedResult ? "YES" : "NO") . ", Got: " . ($hasPermission ? "YES" : "NO") . "\n";
    
    if ($passed) {
        echo "✓ PASS\n\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL\n\n";
        $testsFailed++;
    }
    
    return $passed;
}

echo "Test Group 1: Member role\n";
echo "--------------------------------\n";
testPermission('member', 'manage_projects', false, $testsPassed, $testsFailed);

echo "Test Group 2: Manager role\n";
echo "--------------------------------\n";
testPermission('manager', 'manage_projects', true, $testsPassed, $testsFailed);

echo "Test Group 3: Board role\n";
echo "--------------------------------\n";
testPermission('board', 'manage_projects', true, $testsPassed, $testsFailed);

echo "Test Group 4: Admin role\n";
echo "--------------------------------\n";
testPermission('admin', 'manage_projects', true, $testsPassed, $testsFailed);

echo "Test Group 5: Alumni role\n";
echo "--------------------------------\n";
testPermission('alumni', 'manage_projects', false, $testsPassed, $testsFailed);

// Test draft security check logic
echo "Test Group 6: Draft Security Check Logic\n";
echo "--------------------------------\n";

$draftProject = ['status' => 'draft'];
$openProject = ['status' => 'open'];

// Test member with draft project
$_SESSION['user_role'] = 'member';
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

$shouldBlock = (
    isset($draftProject['status']) && 
    $draftProject['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
);

echo "Testing: Member should be blocked from draft\n";
echo "Expected: YES, Got: " . ($shouldBlock ? "YES" : "NO") . "\n";
if ($shouldBlock) {
    echo "✓ PASS\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL\n\n";
    $testsFailed++;
}

// Test manager with draft project
$_SESSION['user_role'] = 'manager';
$shouldBlock = (
    isset($draftProject['status']) && 
    $draftProject['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
);

echo "Testing: Manager should NOT be blocked from draft\n";
echo "Expected: NO, Got: " . ($shouldBlock ? "YES" : "NO") . "\n";
if (!$shouldBlock) {
    echo "✓ PASS\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL\n\n";
    $testsFailed++;
}

// Test member with open project
$_SESSION['user_role'] = 'member';
$shouldBlock = (
    isset($openProject['status']) && 
    $openProject['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
);

echo "Testing: Member should NOT be blocked from open project\n";
echo "Expected: NO, Got: " . ($shouldBlock ? "YES" : "NO") . "\n";
if (!$shouldBlock) {
    echo "✓ PASS\n\n";
    $testsPassed++;
} else {
    echo "✗ FAIL\n\n";
    $testsFailed++;
}

// Summary
echo "=================================\n";
echo "Test Summary:\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "=================================\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
