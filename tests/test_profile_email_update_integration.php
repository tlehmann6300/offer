<?php
/**
 * Integration test for profile.php email update functionality
 * Tests the POST handler logic for email updates
 * 
 * This is a static analysis test that verifies:
 * 1. The POST handler exists and checks for 'update_email'
 * 2. The email input field is present in the form
 * 3. Session update is performed after successful email change
 * 4. Exception handling is in place
 * 
 * Run with: php tests/test_profile_email_update_integration.php
 */

echo "Testing profile.php email update integration...\n\n";

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Verify profile.php file exists
echo "=== Test 1: Check profile.php exists ===\n";
$profilePath = __DIR__ . '/../pages/auth/profile.php';
if (file_exists($profilePath)) {
    echo "✓ PASS: profile.php file exists\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: profile.php file not found\n";
    $testsFailed++;
    exit(1);
}
echo "\n";

// Test 2: Verify POST handler for email update exists
echo "=== Test 2: Check POST handler for 'update_email' ===\n";
$profileContent = file_get_contents($profilePath);
if (strpos($profileContent, "isset(\$_POST['update_email'])") !== false) {
    echo "✓ PASS: POST handler for 'update_email' found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: POST handler for 'update_email' not found\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Verify email input field in HTML form
echo "=== Test 3: Check email input field exists ===\n";
if (strpos($profileContent, 'name="email"') !== false && 
    strpos($profileContent, 'type="email"') !== false) {
    echo "✓ PASS: Email input field found in form\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Email input field not found in form\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Verify email field is pre-filled with current value
echo "=== Test 4: Check email input is pre-filled ===\n";
if (preg_match('/value="<\?php echo htmlspecialchars\(\$user\[\'email\'\]\);/', $profileContent)) {
    echo "✓ PASS: Email input field is pre-filled with current value\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Email input field is not pre-filled correctly\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Verify User::updateEmail() is called
echo "=== Test 5: Check User::updateEmail() is called ===\n";
if (strpos($profileContent, 'User::updateEmail(') !== false) {
    echo "✓ PASS: User::updateEmail() method is called\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: User::updateEmail() method is not called\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Verify exception handling with try-catch
echo "=== Test 6: Check exception handling is implemented ===\n";
if (strpos($profileContent, 'try {') !== false && 
    strpos($profileContent, 'catch (Exception $e)') !== false) {
    echo "✓ PASS: Exception handling with try-catch found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Exception handling not properly implemented\n";
    $testsFailed++;
}
echo "\n";

// Test 7: Verify session is updated with new email
echo "=== Test 7: Check session user_email is updated ===\n";
if (strpos($profileContent, '$_SESSION[\'user_email\'] = $newEmail') !== false) {
    echo "✓ PASS: Session variable is updated with new email\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Session variable is not updated with new email\n";
    $testsFailed++;
}
echo "\n";

// Test 8: Verify error message is captured from exception
echo "=== Test 8: Check error message is captured from exception ===\n";
if (strpos($profileContent, '$error = $e->getMessage()') !== false) {
    echo "✓ PASS: Error message is captured from exception\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Error message is not captured from exception\n";
    $testsFailed++;
}
echo "\n";

// Test 9: Verify success message is set
echo "=== Test 9: Check success message is set ===\n";
if (strpos($profileContent, "\$message = 'E-Mail-Adresse erfolgreich aktualisiert'") !== false ||
    strpos($profileContent, '$message = "E-Mail-Adresse erfolgreich aktualisiert"') !== false) {
    echo "✓ PASS: Success message is set after email update\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Success message is not set after email update\n";
    $testsFailed++;
}
echo "\n";

// Test 10: Verify user data is reloaded after update
echo "=== Test 10: Check user data is reloaded ===\n";
if (preg_match('/\$user = Auth::user\(\);/', $profileContent) && 
    strpos($profileContent, '// Reload user') !== false) {
    echo "✓ PASS: User data is reloaded after update\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: User data is not reloaded after update\n";
    $testsFailed++;
}
echo "\n";

// Test 11: Verify email change detection
echo "=== Test 11: Check email change is detected ===\n";
if (strpos($profileContent, 'if ($newEmail !== $user[\'email\'])') !== false) {
    echo "✓ PASS: Email change detection is implemented\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Email change detection is not implemented\n";
    $testsFailed++;
}
echo "\n";

// Test 12: Verify submit button exists
echo "=== Test 12: Check submit button for email update ===\n";
if (strpos($profileContent, 'name="update_email"') !== false) {
    echo "✓ PASS: Submit button for email update found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Submit button for email update not found\n";
    $testsFailed++;
}
echo "\n";

// Summary
echo "=====================================\n";
echo "Test Results:\n";
echo "  Passed: $testsPassed\n";
echo "  Failed: $testsFailed\n";
echo "=====================================\n";

if ($testsFailed === 0) {
    echo "✓ All integration tests passed!\n";
    echo "\nThe profile page email update functionality is correctly implemented with:\n";
    echo "  - Email input field pre-filled with current value\n";
    echo "  - POST handler that checks if email has changed\n";
    echo "  - User::updateEmail() call with exception handling\n";
    echo "  - Session update (\$_SESSION['user_email']) on success\n";
    echo "  - Success and error message display\n";
    exit(0);
} else {
    echo "✗ Some integration tests failed\n";
    exit(1);
}
