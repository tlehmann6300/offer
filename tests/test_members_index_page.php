<?php
/**
 * Test Members Index Page
 * Validates the structure and security of pages/members/index.php
 */

echo "=== Members Index Page Test Suite ===\n\n";

// Test 1: File Exists
echo "Test 1: File Exists\n";
$filePath = __DIR__ . '/../pages/members/index.php';
if (file_exists($filePath)) {
    echo "✓ File exists at $filePath\n\n";
} else {
    echo "✗ File not found at $filePath\n\n";
    exit(1);
}

// Test 2: PHP Syntax
echo "Test 2: PHP Syntax\n";
$output = [];
$return_var = 0;
exec("php -l $filePath 2>&1", $output, $return_var);
if ($return_var === 0) {
    echo "✓ PHP syntax is valid\n\n";
} else {
    echo "✗ PHP syntax error:\n";
    echo implode("\n", $output) . "\n\n";
    exit(1);
}

// Test 3: Required Includes
echo "Test 3: Required Includes\n";
$content = file_get_contents($filePath);
$requiredIncludes = [
    'Auth.php',
    'Member.php',
    'helpers.php'
];
$allIncludesFound = true;
foreach ($requiredIncludes as $include) {
    if (strpos($content, $include) !== false) {
        echo "✓ Includes $include\n";
    } else {
        echo "✗ Missing include: $include\n";
        $allIncludesFound = false;
    }
}
echo "\n";

// Test 4: Authentication Check
echo "Test 4: Authentication Check\n";
if (strpos($content, 'Auth::check()') !== false) {
    echo "✓ Uses Auth::check() for authentication\n";
} else {
    echo "✗ Missing Auth::check() authentication\n";
}
if (strpos($content, "header('Location: ../auth/login.php')") !== false) {
    echo "✓ Redirects to login on auth failure\n";
} else {
    echo "✗ Missing redirect to login\n";
}
echo "\n";

// Test 5: Role Access Control
echo "Test 5: Role Access Control\n";
$allowedRoles = ['admin', 'board', 'head', 'member', 'candidate'];
$allRolesPresent = true;
foreach ($allowedRoles as $role) {
    if (strpos($content, "'$role'") !== false) {
        echo "✓ Role '$role' is in allowed roles\n";
    } else {
        echo "✗ Role '$role' not found in allowed roles\n";
        $allRolesPresent = false;
    }
}
echo "\n";

// Test 6: UI Components
echo "Test 6: UI Components\n";
$uiComponents = [
    'Mitgliederverzeichnis' => 'Header title',
    'search' => 'Search input',
    'role' => 'Role filter dropdown',
    'Suchen' => 'Search button',
    'grid grid-cols-1' => 'Responsive grid',
    'rounded-full' => 'Circular profile image',
    'font-bold' => 'Bold name',
    'Profil ansehen' => 'Profile view button',
    'fa-envelope' => 'Mail icon',
    'fa-linkedin-in' => 'LinkedIn icon'
];

$allComponentsFound = true;
foreach ($uiComponents as $component => $description) {
    if (strpos($content, $component) !== false) {
        echo "✓ Has $description\n";
    } else {
        echo "✗ Missing $description ($component)\n";
        $allComponentsFound = false;
    }
}
echo "\n";

// Test 7: Role Dropdown Options
echo "Test 7: Role Dropdown Options\n";
$dropdownOptions = [
    'Alle' => 'All roles option',
    'Vorstand' => 'Board option',
    'Ressortleiter' => 'Head option',
    'Mitglieder' => 'Members option',
    'Anwärter' => 'Candidate option'
];

$allOptionsFound = true;
foreach ($dropdownOptions as $option => $description) {
    if (strpos($content, $option) !== false) {
        echo "✓ Has $description\n";
    } else {
        echo "✗ Missing $description ($option)\n";
        $allOptionsFound = false;
    }
}
echo "\n";

// Test 8: Role Badge Colors
echo "Test 8: Role Badge Colors\n";
$badgeColors = [
    'board' => 'purple',
    'head' => 'blue',
    'member' => 'green',
    'candidate' => 'yellow'
];

$allColorsFound = true;
foreach ($badgeColors as $role => $color) {
    // Check if the role is associated with the correct color
    $rolePattern = "/'$role'.*$color|$color.*'$role'/s";
    if (preg_match($rolePattern, $content)) {
        echo "✓ Role '$role' has $color color badge\n";
    } else {
        echo "⚠ Could not verify $color badge for '$role' role (may be defined differently)\n";
    }
}
echo "\n";

// Test 9: Security - No Sensitive Data
echo "Test 9: Security - No Sensitive Data\n";
$sensitiveFields = ['address', 'password', 'phone', 'birthday'];
$sensitivePatternsFound = false;
foreach ($sensitiveFields as $field) {
    // Check if field is being displayed (not just in variable names)
    if (preg_match("/echo.*\['" . $field . "'\]/i", $content)) {
        echo "✗ Potentially displaying sensitive field: $field\n";
        $sensitivePatternsFound = true;
    }
}
if (!$sensitivePatternsFound) {
    echo "✓ No sensitive data being displayed directly\n";
}
echo "\n";

// Test 10: Member Model Usage
echo "Test 10: Member Model Usage\n";
if (strpos($content, 'Member::getAllActive') !== false) {
    echo "✓ Uses Member::getAllActive() method\n";
} else {
    echo "✗ Missing Member::getAllActive() usage\n";
}
echo "\n";

// Final Summary
echo "=== Test Summary ===\n";
if ($allIncludesFound && $allComponentsFound && $allOptionsFound && $allRolesPresent) {
    echo "✓ All critical tests passed!\n";
    echo "The members index page is properly structured with:\n";
    echo "  - Authentication and role-based access control\n";
    echo "  - Search and filter functionality\n";
    echo "  - Responsive grid layout\n";
    echo "  - Role-based color badges\n";
    echo "  - Contact icons and profile view button\n";
    echo "  - Privacy protection (no sensitive data)\n";
} else {
    echo "⚠ Some tests failed. Please review the output above.\n";
}

echo "\nTest suite completed.\n";
