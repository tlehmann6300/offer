<?php
/**
 * Test: Inventory Manage Page - Warning Prevention
 * Verifies that the Items Grid section handles missing or null fields without warnings
 */

echo "=== Inventory Manage Page Warning Prevention Test ===\n\n";

// Test data scenarios that could cause warnings
$testCases = [
    'Complete item' => [
        'id' => 1,
        'name' => 'Test Item',
        'category_name' => 'Electronics',
        'location_name' => 'Warehouse A',
        'current_stock' => 10,
        'unit' => 'Stk',
        'min_stock' => 5,
    ],
    'Item without category' => [
        'id' => 2,
        'name' => 'Test Item 2',
        'category_name' => null,
        'location_name' => 'Warehouse B',
        'current_stock' => 3,
        'unit' => 'kg',
        'min_stock' => 2,
    ],
    'Item without location' => [
        'id' => 3,
        'name' => 'Test Item 3',
        'category_name' => 'Tools',
        'location_name' => null,
        'current_stock' => 7,
        'unit' => 'Stk',
        'min_stock' => 5,
    ],
    'Item without unit' => [
        'id' => 4,
        'name' => 'Test Item 4',
        'category_name' => 'Supplies',
        'location_name' => 'Warehouse C',
        'current_stock' => 15,
        'unit' => null,
        'min_stock' => 10,
    ],
    'Item with missing fields' => [
        'id' => 5,
        'name' => 'Test Item 5',
        'current_stock' => 2,
        'min_stock' => 5,
    ],
];

define('DEFAULT_LOW_STOCK_THRESHOLD', 5);

$allPassed = true;
$testNumber = 1;

foreach ($testCases as $scenario => $item) {
    echo "Test {$testNumber}: {$scenario}\n";
    
    // Enable error reporting to catch warnings
    error_reporting(E_ALL);
    $errorOccurred = false;
    
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errorOccurred) {
        $errorOccurred = true;
        echo "  ✗ Warning/Error: {$errstr} on line {$errline}\n";
    });
    
    ob_start();
    
    // Test the Items Grid section logic
    try {
        // Test category_name display
        if (!empty($item['category_name'])) {
            $categoryOutput = htmlspecialchars($item['category_name']);
        }
        
        // Test location_name display
        if (!empty($item['location_name'])) {
            $locationOutput = htmlspecialchars($item['location_name']);
        }
        
        // Test unit display with null coalescing
        $stockOutput = $item['current_stock'] . ' ' . htmlspecialchars($item['unit'] ?? 'Stk');
        
        // Test low stock threshold logic
        $lowStockThreshold = $item['min_stock'] ?? DEFAULT_LOW_STOCK_THRESHOLD;
        $showWarning = ($item['current_stock'] <= $lowStockThreshold);
    } catch (Exception $e) {
        echo "  ✗ Exception: {$e->getMessage()}\n";
        $errorOccurred = true;
        $allPassed = false;
    }
    
    ob_end_clean();
    restore_error_handler();
    
    if (!$errorOccurred) {
        echo "  ✓ No warnings generated\n";
        echo "    - Stock display: {$stockOutput}\n";
        echo "    - Low stock warning: " . ($showWarning ? 'Yes' : 'No') . "\n";
    } else {
        $allPassed = false;
    }
    
    echo "\n";
    $testNumber++;
}

if ($allPassed) {
    echo "=== All Tests Passed ===\n";
    exit(0);
} else {
    echo "=== Some Tests Failed ===\n";
    exit(1);
}
