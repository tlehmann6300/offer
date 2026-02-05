<?php
/**
 * Test to verify inventory edit page properly protects EasyVerein-synced items
 * This test validates the requirements from the issue
 * 
 * Run from repository root with: php tests/test_inventory_edit_easyverein_protection.php
 */

echo "Testing Inventory Edit EasyVerein Protection...\n\n";

$editPagePath = realpath(__DIR__ . '/../pages/inventory/edit.php');

if ($editPagePath === false || !file_exists($editPagePath)) {
    echo "✗ Error: Could not find edit.php file\n";
    echo "  Expected location: " . __DIR__ . "/../pages/inventory/edit.php\n";
    exit(1);
}

// Test 1: Verify isSyncedItem check exists
echo "=== Test 1: Verify isSyncedItem check ===\n";

$content = file_get_contents($editPagePath);

if (strpos($content, '$isSyncedItem = !empty($item[\'easyverein_id\']);') !== false) {
    echo "✓ Found easyverein_id check for synced items\n";
} else {
    echo "✗ Missing easyverein_id check\n";
    exit(1);
}

// Test 2: Verify warning banner exists
echo "\n=== Test 2: Verify warning banner for synced items ===\n";

if (strpos($content, 'Stammdaten werden durch EasyVerein verwaltet') !== false) {
    echo "✓ Found warning banner message\n";
} else {
    echo "✗ Missing warning banner\n";
    exit(1);
}

if (preg_match('/if\s*\(\s*\$isSyncedItem\s*\).*?bg-yellow-50/s', $content)) {
    echo "✓ Warning banner is conditionally displayed for synced items\n";
} else {
    echo "✗ Warning banner conditional display not found\n";
    exit(1);
}

// Test 3: Verify readonly fields for synced items
echo "\n=== Test 3: Verify readonly/disabled fields ===\n";

$readonlyFields = [
    'name' => 'readonly',
    'description' => 'readonly',
    'category_id' => 'disabled'
];

$allFieldsFound = true;
foreach ($readonlyFields as $field => $attribute) {
    // Check if field has conditional readonly/disabled attribute
    $pattern = '/name=[\'"]' . preg_quote($field, '/') . '[\'"].*?if\s*\(\s*\$isSyncedItem\s*\).*?' . preg_quote($attribute, '/') . '/s';
    if (preg_match($pattern, $content)) {
        echo "✓ Field '$field' has conditional $attribute attribute\n";
    } else {
        echo "✗ Field '$field' missing conditional $attribute attribute\n";
        $allFieldsFound = false;
    }
}

if (!$allFieldsFound) {
    exit(1);
}

// Test 4: Verify editable fields remain editable
echo "\n=== Test 4: Verify editable fields ===\n";

$editableFields = [
    'location_id',
    'min_stock',
    'unit',
    'unit_price',
    'notes'
];

$allEditableFieldsOk = true;
foreach ($editableFields as $field) {
    // Check that field does NOT have conditional readonly/disabled based on isSyncedItem
    // We look for the field name and ensure it doesn't have the synced item check nearby
    if (preg_match('/name=[\'"]' . preg_quote($field, '/') . '[\'"]/', $content)) {
        // Field exists
        // Check if the field's HTML element specifically has a readonly/disabled conditional
        // We need to be more precise - check within the same element tag
        $pattern = '/name=[\'"]' . preg_quote($field, '/') . '[\'"][^>]*?if\s*\(\s*\$isSyncedItem\s*\).*?(readonly|disabled)[^>]*?>/s';
        if (!preg_match($pattern, $content)) {
            echo "✓ Field '$field' remains editable for synced items\n";
        } else {
            echo "✗ Field '$field' incorrectly made readonly/disabled for synced items\n";
            $allEditableFieldsOk = false;
        }
    }
}

if (!$allEditableFieldsOk) {
    exit(1);
}

// Test 5: Verify backend protection
echo "\n=== Test 5: Verify backend protection logic ===\n";

// Check for conditional data array creation
if (strpos($content, 'if ($isSyncedItem)') !== false &&
    strpos($content, '// Only allow editing local operational data') !== false) {
    echo "✓ Found conditional data array for synced items\n";
} else {
    echo "✗ Missing backend protection logic\n";
    exit(1);
}

// Verify that synced items exclude master data fields
if (preg_match('/if\s*\(\s*\$isSyncedItem\s*\).*?\$data\s*=\s*\[.*?location_id.*?notes.*?\]/s', $content)) {
    // Check that name, description, category_id are NOT in the synced item data array
    $syncedDataPattern = '/if\s*\(\s*\$isSyncedItem\s*\).*?\$data\s*=\s*\[(.*?)\]/s';
    if (preg_match($syncedDataPattern, $content, $matches)) {
        $syncedDataContent = $matches[1];
        if (strpos($syncedDataContent, "'name'") === false &&
            strpos($syncedDataContent, "'description'") === false &&
            strpos($syncedDataContent, "'category_id'") === false) {
            echo "✓ Synced items data array excludes master data fields (name, description, category_id)\n";
        } else {
            echo "✗ Synced items data array incorrectly includes master data fields\n";
            exit(1);
        }
    }
} else {
    echo "✗ Backend protection not properly implemented\n";
    exit(1);
}

echo "\n=== All Tests Passed! ===\n";
echo "✓ Inventory edit page properly protects EasyVerein-synced items\n";
echo "✓ Warning banner is displayed for synced items\n";
echo "✓ Master data fields (name, description, category) are readonly/disabled\n";
echo "✓ Operational fields (location, notes, etc.) remain editable\n";
echo "✓ Backend properly excludes master data from updates\n";

exit(0);
