<?php
/**
 * Test to verify alumni_profiles table schema has new fields
 * Tests that the SQL schema files contain all required fields
 * 
 * Run with: php tests/test_alumni_profiles_schema.php
 */

echo "Testing alumni_profiles table schema...\n\n";

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Verify user_database_schema.sql exists
echo "=== Test 1: Check user_database_schema.sql exists ===\n";
$userSchemaPath = __DIR__ . '/../sql/user_database_schema.sql';
if (file_exists($userSchemaPath)) {
    echo "✓ PASS: user_database_schema.sql file exists\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: user_database_schema.sql file not found\n";
    $testsFailed++;
    exit(1);
}
echo "\n";

// Read schema content for analysis
$schemaContent = file_get_contents($userSchemaPath);

// Test 2: Verify studiengang field exists
echo "=== Test 2: Check studiengang field ===\n";
if (strpos($schemaContent, "studiengang VARCHAR(255) DEFAULT NULL") !== false) {
    echo "✓ PASS: studiengang field found in schema\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: studiengang field not found in schema\n";
    $testsFailed++;
}
echo "\n";

// Test 3: Verify semester field exists
echo "=== Test 3: Check semester field ===\n";
if (strpos($schemaContent, "semester VARCHAR(50) DEFAULT NULL") !== false) {
    echo "✓ PASS: semester field found in schema\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: semester field not found in schema\n";
    $testsFailed++;
}
echo "\n";

// Test 4: Verify angestrebter_abschluss field exists
echo "=== Test 4: Check angestrebter_abschluss field ===\n";
if (strpos($schemaContent, "angestrebter_abschluss VARCHAR(255) DEFAULT NULL") !== false) {
    echo "✓ PASS: angestrebter_abschluss field found in schema\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: angestrebter_abschluss field not found in schema\n";
    $testsFailed++;
}
echo "\n";

// Test 5: Verify about_me field exists
echo "=== Test 5: Check about_me field ===\n";
if (strpos($schemaContent, "about_me TEXT DEFAULT NULL") !== false) {
    echo "✓ PASS: about_me field found in schema\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: about_me field not found in schema\n";
    $testsFailed++;
}
echo "\n";

// Test 6: Verify company field is nullable
echo "=== Test 6: Check company field is nullable ===\n";
if (strpos($schemaContent, "company VARCHAR(255) DEFAULT NULL") !== false) {
    echo "✓ PASS: company field is nullable in schema\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: company field is not nullable in schema\n";
    $testsFailed++;
}
echo "\n";

// Test 7: Verify position field is nullable
echo "=== Test 7: Check position field is nullable ===\n";
if (strpos($schemaContent, "position VARCHAR(255) DEFAULT NULL") !== false) {
    echo "✓ PASS: position field is nullable in schema\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: position field is not nullable in schema\n";
    $testsFailed++;
}
echo "\n";

// Test 8: Verify migration script exists
echo "=== Test 8: Check migration script exists ===\n";
$migrationPath = __DIR__ . '/../sql/migrate_add_profile_fields.php';
if (file_exists($migrationPath)) {
    echo "✓ PASS: Migration script exists\n";
    $testsPassed++;
} else {
    echo "✗ FAIL: Migration script not found\n";
    $testsFailed++;
}
echo "\n";

// Test 9: Verify full_content_schema.sql also updated
echo "=== Test 9: Check full_content_schema.sql is updated ===\n";
$fullSchemaPath = __DIR__ . '/../sql/full_content_schema.sql';
if (file_exists($fullSchemaPath)) {
    $fullSchemaContent = file_get_contents($fullSchemaPath);
    if (strpos($fullSchemaContent, "studiengang VARCHAR(255) DEFAULT NULL") !== false &&
        strpos($fullSchemaContent, "about_me TEXT DEFAULT NULL") !== false) {
        echo "✓ PASS: full_content_schema.sql is updated with new fields\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: full_content_schema.sql is not updated with new fields\n";
        $testsFailed++;
    }
} else {
    echo "✗ FAIL: full_content_schema.sql file not found\n";
    $testsFailed++;
}
echo "\n";

// Test 10: Verify Alumni model includes new fields
echo "=== Test 10: Check Alumni model includes new fields ===\n";
$alumniModelPath = __DIR__ . '/../includes/models/Alumni.php';
if (file_exists($alumniModelPath)) {
    $alumniContent = file_get_contents($alumniModelPath);
    $hasNewFields = strpos($alumniContent, 'studiengang') !== false &&
                    strpos($alumniContent, 'semester') !== false &&
                    strpos($alumniContent, 'angestrebter_abschluss') !== false &&
                    strpos($alumniContent, 'about_me') !== false;
    
    if ($hasNewFields) {
        echo "✓ PASS: Alumni model includes all new fields\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Alumni model is missing some new fields\n";
        $testsFailed++;
    }
} else {
    echo "✗ FAIL: Alumni model file not found\n";
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
    echo "\n✓ All schema tests PASSED!\n";
    exit(0);
} else {
    echo "\n✗ Some schema tests FAILED!\n";
    exit(1);
}
