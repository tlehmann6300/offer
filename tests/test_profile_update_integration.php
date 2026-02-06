<?php
/**
 * Integration test for profile.php profile update functionality
 * Tests the POST handler logic for profile updates
 * 
 * This is a static analysis test that verifies:
 * 1. The POST handler exists and checks for 'update_profile'
 * 2. Alumni model is included
 * 3. Profile form fields are present (common and role-specific)
 * 4. Profile is loaded from alumni_profiles table
 * 5. Security: Only current user can edit their own profile
 * 
 * Run with: php tests/test_profile_update_integration.php
 */

echo "Testing profile.php profile update integration...\n\n";

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

// Read profile.php content for analysis
$profileContent = file_get_contents($profilePath);

// Test 2: Verify Alumni model is included
echo "=== Test 2: Check Alumni model is included ===\n";
if (strpos($profileContent, "require_once __DIR__ . '/../../includes/models/Alumni.php'") !== false) {
    echo "✓ PASS: Alumni model is included\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Alumni model is not included\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Verify profile is loaded from Alumni model
echo "=== Test 3: Check profile is loaded from Alumni::getProfileByUserId ===\n";
if (strpos($profileContent, "Alumni::getProfileByUserId(\$user['id'])") !== false) {
    echo "✓ PASS: Profile is loaded from Alumni model\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Profile is not loaded from Alumni model\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Verify POST handler for profile update exists
echo "=== Test 4: Check POST handler for 'update_profile' ===\n";
if (strpos($profileContent, "isset(\$_POST['update_profile'])") !== false) {
    echo "✓ PASS: POST handler for 'update_profile' found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: POST handler for 'update_profile' not found\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Verify profile update uses Alumni::updateOrCreateProfile
echo "=== Test 5: Check profile update uses Alumni::updateOrCreateProfile ===\n";
if (strpos($profileContent, "Alumni::updateOrCreateProfile(\$user['id']") !== false) {
    echo "✓ PASS: Profile update uses Alumni::updateOrCreateProfile with user ID\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Profile update does not use Alumni::updateOrCreateProfile correctly\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Verify common fields are present in form
echo "=== Test 6: Check common profile fields in form ===\n";
$commonFields = ['first_name', 'last_name', 'profile_email', 'mobile_phone', 'linkedin_url', 'xing_url', 'about_me', 'image_path'];
$missingFields = [];
foreach ($commonFields as $field) {
    if (strpos($profileContent, 'name="' . $field . '"') === false) {
        $missingFields[] = $field;
    }
}
if (empty($missingFields)) {
    echo "✓ PASS: All common fields found in form\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Missing common fields: " . implode(', ', $missingFields) . "\n";
    $testsFailed++;
}
echo "\n";

// Test 7: Verify role-specific fields for candidate/member
echo "=== Test 7: Check candidate/member-specific fields ===\n";
$candidateFields = ['studiengang', 'semester', 'angestrebter_abschluss'];
$candidateFieldsFound = true;
foreach ($candidateFields as $field) {
    if (strpos($profileContent, 'name="' . $field . '"') === false) {
        $candidateFieldsFound = false;
        break;
    }
}
if ($candidateFieldsFound && strpos($profileContent, "in_array(\$user['role'], ['candidate', 'member'])") !== false) {
    echo "✓ PASS: Candidate/member-specific fields found and conditional display implemented\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Candidate/member-specific fields missing or not conditionally displayed\n";
    $testsFailed++;
}
echo "\n";

// Test 8: Verify role-specific fields for alumni
echo "=== Test 8: Check alumni-specific fields ===\n";
$alumniFieldsInForm = strpos($profileContent, 'name="company"') !== false && 
                       strpos($profileContent, 'name="industry"') !== false;
$alumniConditional = strpos($profileContent, "\$user['role'] === 'alumni'") !== false;

if ($alumniFieldsInForm && $alumniConditional) {
    echo "✓ PASS: Alumni-specific fields found and conditional display implemented\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Alumni-specific fields missing or not conditionally displayed\n";
    $testsFailed++;
}
echo "\n";

// Test 9: Verify profile fields are pre-filled with existing data
echo "=== Test 9: Check profile fields are pre-filled ===\n";
if (preg_match('/value="<\?php echo htmlspecialchars\(\$profile\[/', $profileContent)) {
    echo "✓ PASS: Profile fields are pre-filled with existing data\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Profile fields are not pre-filled with existing data\n";
    $testsFailed++;
}
echo "\n";

// Test 10: Verify read-only logic (user can only edit their own profile)
echo "=== Test 10: Check read-only logic (only current user) ===\n";
// The profile update should use $user['id'] from session, not from POST
$usesSessionUserId = strpos($profileContent, "Alumni::updateOrCreateProfile(\$user['id']") !== false;
$noUserIdInForm = strpos($profileContent, 'name="user_id"') === false;

if ($usesSessionUserId && $noUserIdInForm) {
    echo "✓ PASS: Profile update uses session user ID, preventing unauthorized edits\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Profile update security issue - may allow editing other users' profiles\n";
    $testsFailed++;
}
echo "\n";

// Final Summary
echo "===========================================\n";
echo "Test Results:\n";
echo "  Total Tests: " . ($testsPassed + $testsFailed) . "\n";
echo "  Passed: $testsPassed\n";
echo "  Failed: $testsFailed\n";
echo "===========================================\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests PASSED!\n";
    exit(0);
} else {
    echo "\n✗ Some tests FAILED!\n";
    exit(1);
}
