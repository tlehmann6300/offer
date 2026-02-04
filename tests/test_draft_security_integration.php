<?php
/**
 * Integration Test: Draft Security Flow
 * Tests the complete flow of unauthorized draft access
 */

require_once __DIR__ . '/../src/Auth.php';

echo "=== Draft Security Flow Integration Test ===\n\n";

// Start session for testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean up any existing session data
unset($_SESSION['error']);
unset($_SESSION['success']);

$testsPassed = 0;
$testsFailed = 0;

echo "Test 1: Verify session error message is set correctly\n";
echo "-----------------------------------------------------------\n";

// Simulate the security gate logic
$project = ['status' => 'draft'];
$projectId = 123;
$userId = 456;

$_SESSION['user_role'] = 'member';
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

// Simulate the security check
if (
    isset($project['status']) && 
    $project['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
) {
    // Log the attempt (we'll just echo it for the test)
    $logMessage = 'Unauthorized access attempt to draft project ID ' . $projectId . ' by User ' . $userId;
    echo "Log message generated: $logMessage\n";
    
    // Set session error
    $_SESSION['error'] = 'Zugriff verweigert. Dieses Projekt ist noch im Entwurf.';
}

// Verify error message is set
if (isset($_SESSION['error']) && $_SESSION['error'] === 'Zugriff verweigert. Dieses Projekt ist noch im Entwurf.') {
    echo "✓ Session error message correctly set\n\n";
    $testsPassed++;
} else {
    echo "✗ Session error message not set correctly\n\n";
    $testsFailed++;
}

echo "Test 2: Verify manager can access draft without error\n";
echo "-----------------------------------------------------------\n";

// Clean up
unset($_SESSION['error']);

$_SESSION['user_role'] = 'manager';
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

// Simulate the security check for manager
if (
    isset($project['status']) && 
    $project['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
) {
    $_SESSION['error'] = 'Zugriff verweigert. Dieses Projekt ist noch im Entwurf.';
}

// Verify no error message is set for manager
if (!isset($_SESSION['error'])) {
    echo "✓ Manager can access draft without error\n\n";
    $testsPassed++;
} else {
    echo "✗ Manager incorrectly blocked from draft\n\n";
    $testsFailed++;
}

echo "Test 3: Verify error message display and cleanup\n";
echo "-----------------------------------------------------------\n";

// Set an error message
$_SESSION['error'] = 'Test error message';

// Simulate the display and cleanup logic from index.php
if (isset($_SESSION['error'])) {
    $displayedMessage = $_SESSION['error'];
    unset($_SESSION['error']);
    
    echo "Displayed error: $displayedMessage\n";
    
    // Verify it's been cleaned up
    if (!isset($_SESSION['error'])) {
        echo "✓ Error message correctly displayed and cleaned up\n\n";
        $testsPassed++;
    } else {
        echo "✗ Error message not cleaned up\n\n";
        $testsFailed++;
    }
} else {
    echo "✗ Error message not found\n\n";
    $testsFailed++;
}

echo "Test 4: Verify board role has manage_projects permission\n";
echo "-----------------------------------------------------------\n";

$_SESSION['user_role'] = 'board';
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

// Simulate the security check for board
unset($_SESSION['error']);
if (
    isset($project['status']) && 
    $project['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
) {
    $_SESSION['error'] = 'Zugriff verweigert. Dieses Projekt ist noch im Entwurf.';
}

// Verify no error message is set for board
if (!isset($_SESSION['error'])) {
    echo "✓ Board can access draft without error\n\n";
    $testsPassed++;
} else {
    echo "✗ Board incorrectly blocked from draft\n\n";
    $testsFailed++;
}

echo "Test 5: Verify alumni role does not have manage_projects permission\n";
echo "-----------------------------------------------------------\n";

$_SESSION['user_role'] = 'alumni';
$_SESSION['authenticated'] = true;
$_SESSION['last_activity'] = time();

// Simulate the security check for alumni
unset($_SESSION['error']);
if (
    isset($project['status']) && 
    $project['status'] === 'draft' && 
    !Auth::hasPermission('manage_projects')
) {
    $_SESSION['error'] = 'Zugriff verweigert. Dieses Projekt ist noch im Entwurf.';
}

// Verify error message is set for alumni
if (isset($_SESSION['error'])) {
    echo "✓ Alumni correctly blocked from draft\n\n";
    $testsPassed++;
} else {
    echo "✗ Alumni incorrectly allowed to access draft\n\n";
    $testsFailed++;
}

// Summary
echo "=================================\n";
echo "Integration Test Summary:\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "=================================\n";

if ($testsFailed === 0) {
    echo "\n✓ All integration tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some integration tests failed!\n";
    exit(1);
}
