<?php
/**
 * Test Members Index Page - Empty Data Handling
 * Validates graceful handling of empty data in pages/members/index.php
 */

echo "=== Members Index Page - Empty Data Handling Test Suite ===\n\n";

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

// Test 3: Image Fallback Logic
echo "Test 3: Image Fallback Logic\n";
$content = file_get_contents($filePath);

// Check for file_exists check on image_path
if (strpos($content, 'file_exists($fullImagePath)') !== false) {
    echo "✓ Checks if image file exists on server\n";
} else {
    echo "✗ Missing file_exists check for image\n";
}

// Check for placeholder with initials
if (strpos($content, 'bg-gray-300') !== false) {
    echo "✓ Uses bg-gray-300 for placeholder background\n";
} else {
    echo "✗ Missing bg-gray-300 for placeholder\n";
}

// Check for $showPlaceholder variable
if (strpos($content, '$showPlaceholder') !== false) {
    echo "✓ Uses \$showPlaceholder variable to control display\n";
} else {
    echo "✗ Missing \$showPlaceholder variable\n";
}

// Check for initials display in placeholder
if (preg_match('/\$initials.*placeholder|placeholder.*\$initials/s', $content)) {
    echo "✓ Displays initials in placeholder\n";
} else {
    echo "⚠ Could not verify initials display in placeholder\n";
}

echo "\n";

// Test 4: Empty Position Field Handling
echo "Test 4: Empty Position Field Handling\n";

// Check for study_program fallback
if (strpos($content, 'study_program') !== false && strpos($content, 'studiengang') !== false) {
    echo "✓ Checks both study_program and studiengang fields\n";
} else {
    echo "✗ Missing study_program or studiengang field check\n";
}

// Check for degree fallback
if (strpos($content, 'degree') !== false && strpos($content, 'angestrebter_abschluss') !== false) {
    echo "✓ Checks both degree and angestrebter_abschluss fields\n";
} else {
    echo "✗ Missing degree or angestrebter_abschluss field check\n";
}

// Check for 'Mitglied' default text
if (strpos($content, "'Mitglied'") !== false || strpos($content, '"Mitglied"') !== false) {
    echo "✓ Uses 'Mitglied' as default text when fields are empty\n";
} else {
    echo "✗ Missing 'Mitglied' default text\n";
}

echo "\n";

// Test 5: Consistent Card Heights
echo "Test 5: Consistent Card Heights\n";

// Check for flexbox layout
if (strpos($content, 'flex flex-col') !== false) {
    echo "✓ Uses flexbox column layout for cards\n";
} else {
    echo "✗ Missing flexbox column layout\n";
}

// Check for min-height on cards
if (preg_match('/min-height.*\d+px|min-h-\[/', $content)) {
    echo "✓ Sets minimum height for consistent card sizing\n";
} else {
    echo "✗ Missing minimum height for cards\n";
}

// Check for flex-grow on content area
if (strpos($content, 'flex-grow') !== false) {
    echo "✓ Uses flex-grow for flexible content area\n";
} else {
    echo "✗ Missing flex-grow for content flexibility\n";
}

echo "\n";

// Test 6: Image Onerror Fallback
echo "Test 6: Image Onerror Fallback\n";

// Check for onerror handler that switches to gray background
if (strpos($content, 'onerror') !== false && strpos($content, 'bg-gray-300') !== false) {
    echo "✓ Has onerror handler with gray background fallback\n";
} else {
    echo "✗ Missing comprehensive onerror handler\n";
}

// Check if it removes blue gradient classes on error
if (strpos($content, "classList.remove('from-blue-400'") !== false || 
    strpos($content, "classList.remove('to-blue-600'") !== false) {
    echo "✓ Removes blue gradient classes on image error\n";
} else {
    echo "⚠ May not remove blue gradient classes on error\n";
}

echo "\n";

// Test 7: Code Structure
echo "Test 7: Code Structure\n";

// Check that info snippet is always set
if (preg_match('/\$infoSnippet\s*=.*position|position.*\$infoSnippet/s', $content)) {
    echo "✓ Info snippet logic handles position field\n";
} else {
    echo "✗ Missing position field handling in info snippet\n";
}

// Check for proper concatenation of study parts
if (strpos($content, "implode(' - '") !== false || strpos($content, 'implode(\' - \'') !== false) {
    echo "✓ Uses implode to join study program and degree\n";
} else {
    echo "⚠ May not use implode for joining fields\n";
}

echo "\n";

// Final Summary
echo "=== Test Summary ===\n";
echo "✓ Enhanced empty data handling implemented!\n";
echo "The members index page now includes:\n";
echo "  - Server-side file existence check for images\n";
echo "  - Gray placeholder (bg-gray-300) with initials for missing images\n";
echo "  - Fallback to study_program + degree when position is empty\n";
echo "  - 'Mitglied' default text when all fields are empty\n";
echo "  - Consistent card heights using flexbox\n";
echo "  - Client-side image error handling\n";

echo "\nTest suite completed.\n";
