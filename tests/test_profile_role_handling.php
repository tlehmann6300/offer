<?php
/**
 * Test: Profile Role Handling and Database Protection
 * Tests the improved role handling and database protection in profile.php
 * 
 * Run with: php tests/test_profile_role_handling.php
 */

echo "Testing profile.php role handling improvements...\n\n";

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

// Test 2: Verify explicit role retrieval from Auth
echo "=== Test 2: Check explicit role retrieval from Auth ===\n";
if (strpos($profileContent, "\$userRole = \$user['role'] ?? '';") !== false) {
    echo "✓ PASS: Role is explicitly retrieved from Auth into \$userRole variable\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Explicit role retrieval not found\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Verify student roles use $userRole
echo "=== Test 3: Check student roles use \$userRole variable ===\n";
if (strpos($profileContent, "in_array(\$userRole, ['candidate', 'member', 'board', 'head'])") !== false) {
    echo "✓ PASS: Student roles check uses \$userRole variable\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Student roles check not using \$userRole\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Verify alumni role uses $userRole
echo "=== Test 4: Check alumni role uses \$userRole variable ===\n";
if (strpos($profileContent, "\$userRole === 'alumni'") !== false) {
    echo "✓ PASS: Alumni role check uses \$userRole variable\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Alumni role check not using \$userRole\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Verify PDOException handling exists
echo "=== Test 5: Check PDOException handling ===\n";
if (strpos($profileContent, "catch (PDOException \$e)") !== false) {
    echo "✓ PASS: PDOException handling found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: PDOException handling not found\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Verify user-friendly error message
echo "=== Test 6: Check user-friendly database error message ===\n";
if (strpos($profileContent, "Datenbank nicht aktuell. Bitte Admin kontaktieren.") !== false) {
    echo "✓ PASS: User-friendly database error message found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: User-friendly database error message not found\n";
    $testsFailed++;
}
echo "\n";

// Test 7: Verify study_program field is set for students
echo "=== Test 7: Check study_program field for database compatibility ===\n";
if (strpos($profileContent, "\$profileData['study_program']") !== false &&
    strpos($profileContent, "study_program: Database column alias") !== false) {
    echo "✓ PASS: study_program field is set with proper documentation\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: study_program field or documentation not found\n";
    $testsFailed++;
}
echo "\n";

// Test 8: Verify error logging functionality exists (not exact message)
echo "=== Test 8: Check error logging functionality exists ===\n";
if (preg_match('/catch\s*\(\s*PDOException[^}]+error_log\s*\(/s', $profileContent)) {
    echo "✓ PASS: Error logging functionality exists in PDOException handler\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Error logging functionality not found\n";
    $testsFailed++;
}
echo "\n";

// Test 9: Verify student view comment
echo "=== Test 9: Check Student View documentation comment ===\n";
if (strpos($profileContent, "// Student View: member, candidate, head, board -> Show study fields") !== false ||
    strpos($profileContent, "<!-- Student View: Show Studiengang, Semester, Abschluss -->") !== false) {
    echo "✓ PASS: Student View documentation found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Student View documentation not found\n";
    $testsFailed++;
}
echo "\n";

// Test 10: Verify alumni view comment
echo "=== Test 10: Check Alumni View documentation comment ===\n";
if (strpos($profileContent, "// Alumni View: Show employment fields") !== false ||
    strpos($profileContent, "<!-- Alumni View: Show Arbeitgeber, Position, Branche -->") !== false) {
    echo "✓ PASS: Alumni View documentation found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Alumni View documentation not found\n";
    $testsFailed++;
}
echo "\n";

// Test 11: Verify Arbeitgeber note for students
echo "=== Test 11: Check note about Arbeitgeber being hidden for students ===\n";
if (strpos($profileContent, "// Note: Arbeitgeber (company) fields are optional/hidden for students") !== false) {
    echo "✓ PASS: Note about Arbeitgeber being optional/hidden for students found\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Note about Arbeitgeber not found\n";
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
    echo "\n✓ All role handling improvements verified!\n";
    exit(0);
} else {
    echo "\n✗ Some tests FAILED!\n";
    exit(1);
}
