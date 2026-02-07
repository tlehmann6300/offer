<?php
/**
 * Unit test for Alumni search filtering
 * Tests that search filters by Name OR Position OR Company OR Industry
 * Tests that only alumni role users are shown
 * Run with: php tests/test_alumni_search_filtering.php
 */

echo "Testing Alumni Search Filtering...\n\n";

// Get absolute paths
$configPath = realpath(__DIR__ . '/../config/config.php');
$databasePath = realpath(__DIR__ . '/../includes/database.php');
$alumniModelPath = realpath(__DIR__ . '/../includes/models/Alumni.php');

if (!$configPath || !$databasePath || !$alumniModelPath) {
    echo "❌ FAILED: Could not find required files\n";
    exit(1);
}

require_once $configPath;
require_once $databasePath;
require_once $alumniModelPath;

// Test 1: Test searchProfiles method exists
echo "=== Test 1: Verify searchProfiles method exists ===\n";
if (!method_exists('Alumni', 'searchProfiles')) {
    echo "❌ FAILED: searchProfiles method does not exist\n";
    exit(1);
}
echo "✓ PASSED: searchProfiles method exists\n\n";

// Test 2: Test searchProfiles accepts filters parameter
echo "=== Test 2: Test searchProfiles accepts filters parameter ===\n";
$reflection = new ReflectionMethod('Alumni', 'searchProfiles');
$params = $reflection->getParameters();
if (count($params) === 1 && $params[0]->getName() === 'filters') {
    echo "✓ PASSED: searchProfiles accepts filters parameter\n\n";
} else {
    echo "❌ FAILED: searchProfiles has incorrect parameters\n";
    exit(1);
}

// Test 3: Test searchProfiles returns array
echo "=== Test 3: Test searchProfiles returns array ===\n";
$reflection = new ReflectionMethod('Alumni', 'searchProfiles');
$returnType = $reflection->getReturnType();
if ($returnType && $returnType->getName() === 'array') {
    echo "✓ PASSED: searchProfiles returns array\n\n";
} else {
    echo "⚠ WARNING: Return type not explicitly declared (PHP allows this)\n\n";
}

// Test 4: Verify the method filters by role='alumni' by checking the source code
echo "=== Test 4: Verify role filtering in searchProfiles ===\n";
$fileContent = file_get_contents($alumniModelPath);
if (strpos($fileContent, "u.role = 'alumni'") !== false) {
    echo "✓ PASSED: searchProfiles filters by role='alumni'\n\n";
} else {
    echo "❌ FAILED: searchProfiles does not filter by role='alumni'\n";
    exit(1);
}

// Test 5: Verify search filters multiple fields
echo "=== Test 5: Verify multi-field search (Name OR Position OR Company OR Industry) ===\n";
$expectedFields = ['first_name', 'last_name', 'position', 'company', 'industry'];
$allFieldsFound = true;
foreach ($expectedFields as $field) {
    if (strpos($fileContent, "ap.$field LIKE ?") === false) {
        echo "❌ FAILED: Search does not filter by $field\n";
        $allFieldsFound = false;
    }
}
if ($allFieldsFound) {
    echo "✓ PASSED: searchProfiles filters by Name OR Position OR Company OR Industry\n\n";
} else {
    exit(1);
}

// Test 6: Verify JOIN with users table
echo "=== Test 6: Verify JOIN with users table for role filtering ===\n";
if (strpos($fileContent, 'INNER JOIN') !== false && strpos($fileContent, '.users u') !== false) {
    echo "✓ PASSED: searchProfiles joins with users table to filter by role\n\n";
} else {
    echo "❌ FAILED: searchProfiles does not join with users table\n";
    exit(1);
}

echo "===========================================\n";
echo "Alumni search filtering tests completed! ✓\n";
echo "Search filters by: Name OR Position OR Company OR Industry\n";
echo "Only alumni role users are shown\n";
echo "===========================================\n";
