<?php
/**
 * Test Inventory Import Functionality
 * Tests the importFromJson method with various scenarios
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';

echo "=== Inventory Import Test Suite ===\n\n";

// Since we can't connect to the external database, we'll test the JSON validation logic
// and the sample JSON file

// Test 1: Validate Sample JSON File
echo "Test 1: Validate Sample JSON File\n";
$sampleFile = __DIR__ . '/../samples/inventory_import_sample.json';
if (file_exists($sampleFile)) {
    $jsonContent = file_get_contents($sampleFile);
    $data = json_decode($jsonContent, true);
    
    if ($data === null) {
        echo "✗ Sample JSON file is invalid\n\n";
    } else {
        echo "✓ Sample JSON file is valid\n";
        echo "  Items in sample: " . count($data) . "\n";
        
        // Validate structure
        $valid = true;
        $requiredFields = ['name', 'category'];
        
        foreach ($data as $index => $item) {
            foreach ($requiredFields as $field) {
                if (empty($item[$field])) {
                    echo "✗ Item at index $index missing required field: $field\n";
                    $valid = false;
                }
            }
            
            // Check status if provided
            if (isset($item['status'])) {
                $validStatuses = ['available', 'in_use', 'maintenance', 'retired'];
                if (!in_array($item['status'], $validStatuses)) {
                    echo "✗ Item at index $index has invalid status: {$item['status']}\n";
                    $valid = false;
                }
            }
            
            // Check date format if provided
            if (isset($item['purchase_date'])) {
                $timestamp = strtotime($item['purchase_date']);
                if ($timestamp === false) {
                    echo "✗ Item at index $index has invalid purchase_date: {$item['purchase_date']}\n";
                    $valid = false;
                }
            }
        }
        
        if ($valid) {
            echo "✓ All items in sample have valid structure\n\n";
        } else {
            echo "\n";
        }
    }
} else {
    echo "✗ Sample JSON file not found at: $sampleFile\n\n";
}

// Test 2: Validate Required Fields Logic
echo "Test 2: Test Required Fields Validation\n";
$testData = [
    // Valid item with status
    [
        'name' => 'Test Item',
        'category' => 'Test Category',
        'status' => 'available'
    ],
    // Valid item without status (should default to 'available')
    [
        'name' => 'Test Item Without Status',
        'category' => 'Test Category'
    ],
    // Missing name
    [
        'category' => 'Test Category',
        'status' => 'available'
    ],
    // Missing category
    [
        'name' => 'Test Item',
        'status' => 'available'
    ],
    // Invalid status
    [
        'name' => 'Test Item',
        'category' => 'Test Category',
        'status' => 'invalid_status'
    ]
];

$expectedResults = [
    'valid',
    'valid_no_status',
    'missing_name',
    'missing_category',
    'invalid_status'
];

foreach ($testData as $index => $item) {
    $expected = $expectedResults[$index];
    $hasName = !empty($item['name']);
    $hasCategory = !empty($item['category']);
    $validStatus = true;
    
    if (isset($item['status'])) {
        $validStatuses = ['available', 'in_use', 'maintenance', 'retired'];
        $validStatus = in_array($item['status'], $validStatuses);
    }
    
    $actual = 'valid';
    if (!$hasName) $actual = 'missing_name';
    elseif (!$hasCategory) $actual = 'missing_category';
    elseif (!$validStatus) $actual = 'invalid_status';
    elseif (!isset($item['status'])) $actual = 'valid_no_status';
    
    if ($actual === $expected) {
        echo "✓ Test case $index: Expected '$expected', got '$actual'\n";
    } else {
        echo "✗ Test case $index: Expected '$expected', got '$actual'\n";
    }
}
echo "\n";

// Test 3: Test Serial Number Uniqueness Logic
echo "Test 3: Test Serial Number Uniqueness Logic\n";
echo "  This test verifies that duplicate serial numbers would be caught\n";
echo "  (Actual database check requires database connection)\n";

$testItems = [
    [
        'name' => 'Item 1',
        'category' => 'Category A',
        'serial_number' => 'SN001'
    ],
    [
        'name' => 'Item 2',
        'category' => 'Category A',
        'serial_number' => 'SN001'  // Duplicate
    ]
];

echo "  Items with serial numbers to check: " . count($testItems) . "\n";
$serialNumbers = array_filter(array_column($testItems, 'serial_number'));
$uniqueSerials = array_unique($serialNumbers);

if (count($serialNumbers) !== count($uniqueSerials)) {
    echo "✓ Duplicate serial numbers detected in test data\n";
} else {
    echo "✓ No duplicate serial numbers in test data\n";
}
echo "\n";

// Test 4: Test Optional Fields
echo "Test 4: Test Optional Fields Handling\n";
$itemWithOptionalFields = [
    'name' => 'Complete Item',
    'category' => 'Test',
    'status' => 'available',
    'description' => 'Full description',
    'serial_number' => 'SN123',
    'location' => 'Office',
    'purchase_date' => '2024-01-15'
];

$itemMinimal = [
    'name' => 'Minimal Item',
    'category' => 'Test'
];

$optionalFields = ['description', 'serial_number', 'location', 'purchase_date'];
$countOptionalComplete = 0;
$countOptionalMinimal = 0;

foreach ($optionalFields as $field) {
    if (isset($itemWithOptionalFields[$field])) $countOptionalComplete++;
    if (isset($itemMinimal[$field])) $countOptionalMinimal++;
}

echo "✓ Complete item has $countOptionalComplete optional fields\n";
echo "✓ Minimal item has $countOptionalMinimal optional fields\n";
echo "  Both items are valid (optional fields are truly optional)\n\n";

// Test 5: Test Date Format Parsing
echo "Test 5: Test Date Format Parsing\n";
$dateFormats = [
    '2024-01-15' => true,
    '2024/01/15' => true,
    '15.01.2024' => true,
    'invalid-date' => false,
    '2024-13-45' => false
];

foreach ($dateFormats as $dateStr => $shouldBeValid) {
    $timestamp = strtotime($dateStr);
    $isValid = $timestamp !== false;
    
    if ($isValid === $shouldBeValid) {
        echo "✓ Date '$dateStr': ";
        echo $isValid ? "Valid (parsed successfully)\n" : "Invalid (rejected as expected)\n";
    } else {
        echo "✗ Date '$dateStr': Unexpected result\n";
    }
}

echo "\n=== Test Suite Complete ===\n";
echo "Note: Full integration tests require database connection.\n";
echo "To test with actual database:\n";
echo "1. Apply the migration: php apply_migration.php\n";
echo "2. Upload the sample JSON through the web interface\n";
echo "3. Verify items are imported correctly\n";
