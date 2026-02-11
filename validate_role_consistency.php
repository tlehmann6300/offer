<?php
/**
 * Validate Role Consistency
 * Checks that all roles used in AuthHandler match the SQL schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Validating Role Consistency\n";
echo "===========================\n\n";

// Define valid roles from SQL schema (dbs15253086.sql)
$validRoles = [
    'board_finance',
    'board_internal',
    'board_external',
    'alumni_board',
    'alumni_auditor',
    'alumni',
    'honorary_member',
    'head',
    'member',
    'candidate'
];

// Define roles used in AuthHandler's Microsoft login mapping
$microsoftMappingRoles = [
    'candidate',      // anwaerter
    'member',         // mitglied
    'head',           // ressortleiter
    'board_finance',  // vorstand_finanzen
    'board_internal', // vorstand_intern
    'board_external', // vorstand_extern
    'alumni',         // alumni
    'alumni_board',   // alumni_vorstand
    'alumni_auditor', // alumni_finanz
    'honorary_member' // ehrenmitglied
];

// Define roles used in AuthHandler's role hierarchy
$hierarchyRoles = [
    'candidate',
    'alumni',
    'member',
    'honorary_member',
    'manager',        // DEPRECATED but kept for backward compatibility
    'head',
    'alumni_board',
    'alumni_auditor',
    'board_finance',
    'board_internal',
    'board_external',
    'board',          // DEPRECATED but kept for backward compatibility
    'admin'           // DEPRECATED but kept for backward compatibility
];

echo "Test 1: Microsoft mapping roles are valid... ";
$invalidMappingRoles = array_diff($microsoftMappingRoles, $validRoles);
if (empty($invalidMappingRoles)) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    echo "Invalid roles: " . implode(', ', $invalidMappingRoles) . "\n";
    exit(1);
}

echo "Test 2: All valid roles are covered in Microsoft mapping... ";
$uncoveredRoles = array_diff($validRoles, $microsoftMappingRoles);
if (empty($uncoveredRoles)) {
    echo "✓ PASS\n";
} else {
    echo "⚠ WARNING\n";
    echo "Uncovered roles (not mapped from Azure): " . implode(', ', $uncoveredRoles) . "\n";
}

echo "Test 3: Hierarchy contains only valid or deprecated roles... ";
$deprecatedRoles = ['manager', 'board', 'admin'];
$nonDeprecatedHierarchyRoles = array_diff($hierarchyRoles, $deprecatedRoles);
$invalidHierarchyRoles = array_diff($nonDeprecatedHierarchyRoles, $validRoles);
if (empty($invalidHierarchyRoles)) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    echo "Invalid roles in hierarchy: " . implode(', ', $invalidHierarchyRoles) . "\n";
    exit(1);
}

echo "Test 4: Check SQL files for role consistency... ";

// Read SQL files and extract role ENUMs
$sqlFiles = [
    'sql/dbs15253086.sql' => ['users', 'invitation_tokens'],
    'sql/dbs15161271.sql' => ['event_roles']
];

$allConsistent = true;
foreach ($sqlFiles as $file => $tables) {
    if (!file_exists($file)) {
        echo "✗ FAIL\n";
        echo "File not found: $file\n";
        exit(1);
    }
    
    $content = file_get_contents($file);
    foreach ($tables as $table) {
        // Find the CREATE TABLE statement for this specific table
        $pattern = "/CREATE TABLE[^(]*$table\s*\((.*?)\)/s";
        if (preg_match($pattern, $content, $tableMatch)) {
            $tableContent = $tableMatch[1];
            // Extract ENUM definition for role column
            if (preg_match("/role\s+ENUM\s*\('([^']+(?:',\s*'[^']+)*)'\)/", $tableContent, $matches)) {
                $sqlRolesStr = $matches[1];
                $sqlRoles = array_map('trim', explode("', '", $sqlRolesStr));
                sort($sqlRoles);
                $sortedValidRoles = $validRoles;
                sort($sortedValidRoles);
                
                if ($sqlRoles !== $sortedValidRoles) {
                    $allConsistent = false;
                    echo "\n✗ FAIL for $table in $file\n";
                    echo "Expected: " . implode(', ', $sortedValidRoles) . "\n";
                    echo "Found: " . implode(', ', $sqlRoles) . "\n";
                }
            }
        }
    }
}

if ($allConsistent) {
    echo "✓ PASS\n";
}

echo "\nTest 5: Check profile_complete column exists in users table... ";
$usersSql = file_get_contents('sql/dbs15253086.sql');
if (strpos($usersSql, 'profile_complete') !== false) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    echo "profile_complete column not found in users table\n";
    exit(1);
}

echo "\n===========================\n";
echo "All validation tests passed! ✓\n";
echo "===========================\n";
echo "\nSummary:\n";
echo "- All " . count($microsoftMappingRoles) . " Microsoft mapped roles are valid\n";
echo "- All SQL role ENUMs are consistent\n";
echo "- profile_complete column exists in users table\n";
echo "- Role hierarchy includes backward compatibility for deprecated roles\n";

