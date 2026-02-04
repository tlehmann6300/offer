<?php
/**
 * Unit test for User::updateEmail method
 * Tests email update functionality with duplicate checking
 * Run with: php tests/test_user_update_email.php
 */

echo "Testing User::updateEmail method...\n\n";

// Load required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/User.php';

$testsPassed = 0;
$testsFailed = 0;

/**
 * Test helper: Create a test user and return the ID
 */
function createTestUser($email, $role = 'member') {
    $password = 'testpassword' . rand(1000, 9999);
    return User::create($email, $password, $role);
}

/**
 * Test helper: Delete a user by ID
 */
function deleteTestUser($userId) {
    User::delete($userId);
}

// Test 1: Successfully update email when new email is available
echo "=== Test 1: Update email to an available address ===\n";
try {
    $userId1 = createTestUser('original@test.com');
    
    // Update to a new email
    $result = User::updateEmail($userId1, 'newemail@test.com');
    
    // Verify the update
    $user = User::getById($userId1);
    
    if ($result === true && $user['email'] === 'newemail@test.com') {
        echo "✓ PASS: Email updated successfully\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Email was not updated correctly\n";
        echo "  Expected: newemail@test.com\n";
        echo "  Got: " . $user['email'] . "\n";
        $testsFailed++;
    }
    
    // Cleanup
    deleteTestUser($userId1);
} catch (Exception $e) {
    echo "✗ FAIL: Unexpected exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 2: Throw exception when email is already taken by another user
echo "=== Test 2: Throw exception when email is already taken ===\n";
try {
    $userId1 = createTestUser('user1@test.com');
    $userId2 = createTestUser('user2@test.com');
    
    // Try to update user2's email to user1's email
    $exceptionThrown = false;
    $exceptionMessage = '';
    
    try {
        User::updateEmail($userId2, 'user1@test.com');
    } catch (Exception $e) {
        $exceptionThrown = true;
        $exceptionMessage = $e->getMessage();
    }
    
    if ($exceptionThrown && $exceptionMessage === 'E-Mail bereits vergeben') {
        echo "✓ PASS: Exception thrown with correct message\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Expected exception not thrown or wrong message\n";
        echo "  Expected: 'E-Mail bereits vergeben'\n";
        echo "  Got: '$exceptionMessage'\n";
        $testsFailed++;
    }
    
    // Verify user2's email was not changed
    $user2 = User::getById($userId2);
    if ($user2['email'] === 'user2@test.com') {
        echo "✓ PASS: Email was not changed after exception\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Email was changed despite exception\n";
        $testsFailed++;
    }
    
    // Cleanup
    deleteTestUser($userId1);
    deleteTestUser($userId2);
} catch (Exception $e) {
    echo "✗ FAIL: Unexpected exception in test setup: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Allow user to update to their own current email (edge case)
echo "=== Test 3: Update to same email (edge case) ===\n";
try {
    $userId1 = createTestUser('sameemail@test.com');
    
    // Update to the same email
    $result = User::updateEmail($userId1, 'sameemail@test.com');
    
    if ($result === true) {
        echo "✓ PASS: Can update to same email without exception\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Update to same email failed\n";
        $testsFailed++;
    }
    
    // Cleanup
    deleteTestUser($userId1);
} catch (Exception $e) {
    echo "✗ FAIL: Unexpected exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Verify COUNT check works correctly (not SELECT *)
echo "=== Test 4: Verify email uniqueness check is precise ===\n";
try {
    // Create three users
    $userId1 = createTestUser('unique1@test.com');
    $userId2 = createTestUser('unique2@test.com');
    $userId3 = createTestUser('unique3@test.com');
    
    // Try to update user3 to user1's email (should fail)
    $exceptionThrown = false;
    try {
        User::updateEmail($userId3, 'unique1@test.com');
    } catch (Exception $e) {
        $exceptionThrown = true;
    }
    
    if ($exceptionThrown) {
        echo "✓ PASS: Correctly detected duplicate email\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Did not detect duplicate email\n";
        $testsFailed++;
    }
    
    // Now update user3 to a truly unique email (should succeed)
    $result = User::updateEmail($userId3, 'newemail3@test.com');
    $user3 = User::getById($userId3);
    
    if ($result === true && $user3['email'] === 'newemail3@test.com') {
        echo "✓ PASS: Successfully updated to unique email\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Failed to update to unique email\n";
        $testsFailed++;
    }
    
    // Cleanup
    deleteTestUser($userId1);
    deleteTestUser($userId2);
    deleteTestUser($userId3);
} catch (Exception $e) {
    echo "✗ FAIL: Unexpected exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Validate email format
echo "=== Test 5: Throw exception for invalid email format ===\n";
try {
    $userId1 = createTestUser('valid@test.com');
    
    // Try to update to an invalid email
    $exceptionThrown = false;
    $exceptionMessage = '';
    
    try {
        User::updateEmail($userId1, 'not-a-valid-email');
    } catch (Exception $e) {
        $exceptionThrown = true;
        $exceptionMessage = $e->getMessage();
    }
    
    if ($exceptionThrown && $exceptionMessage === 'Ungültige E-Mail-Adresse') {
        echo "✓ PASS: Exception thrown for invalid email format\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Expected exception for invalid email\n";
        echo "  Expected: 'Ungültige E-Mail-Adresse'\n";
        echo "  Got: '$exceptionMessage'\n";
        $testsFailed++;
    }
    
    // Cleanup
    deleteTestUser($userId1);
} catch (Exception $e) {
    echo "✗ FAIL: Unexpected exception: " . $e->getMessage() . "\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Throw exception for non-existent user
echo "=== Test 6: Throw exception when user ID doesn't exist ===\n";
try {
    $nonExistentUserId = 99999999;
    
    $exceptionThrown = false;
    $exceptionMessage = '';
    
    try {
        User::updateEmail($nonExistentUserId, 'newemail@test.com');
    } catch (Exception $e) {
        $exceptionThrown = true;
        $exceptionMessage = $e->getMessage();
    }
    
    if ($exceptionThrown && $exceptionMessage === 'Benutzer nicht gefunden') {
        echo "✓ PASS: Exception thrown for non-existent user\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Expected exception for non-existent user\n";
        echo "  Expected: 'Benutzer nicht gefunden'\n";
        echo "  Got: '$exceptionMessage'\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Unexpected exception: " . $e->getMessage() . "\n";
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
    echo "✓ All tests passed!\n";
    exit(0);
} else {
    echo "✗ Some tests failed\n";
    exit(1);
}
