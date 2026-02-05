<?php
/**
 * Test Inventory Quantity Fields
 * Verifies that getAll() and getById() return quantity and available_quantity fields
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';

echo "=== Inventory Quantity Fields Test ===\n\n";

try {
    // Test 1: Verify getAll() returns quantity and available_quantity
    echo "Test 1: Verify getAll() returns quantity and available_quantity\n";
    $items = Inventory::getAll();
    
    if (empty($items)) {
        echo "⚠ No items found in inventory (empty database)\n";
        echo "✓ Test passed (no errors, but database is empty)\n\n";
    } else {
        $firstItem = $items[0];
        $hasQuantity = array_key_exists('quantity', $firstItem);
        $hasAvailableQuantity = array_key_exists('available_quantity', $firstItem);
        
        if ($hasQuantity && $hasAvailableQuantity) {
            echo "✓ Both 'quantity' and 'available_quantity' fields are present\n";
            echo "  Sample item: {$firstItem['name']}\n";
            echo "  - quantity: {$firstItem['quantity']}\n";
            echo "  - available_quantity: {$firstItem['available_quantity']}\n";
            echo "  - current_stock: {$firstItem['current_stock']}\n\n";
        } else {
            if (!$hasQuantity) echo "✗ Field 'quantity' is missing\n";
            if (!$hasAvailableQuantity) echo "✗ Field 'available_quantity' is missing\n";
            echo "\n";
            exit(1);
        }
    }
    
    // Test 2: Verify getById() returns quantity and available_quantity
    echo "Test 2: Verify getById() returns quantity and available_quantity\n";
    
    if (!empty($items)) {
        $itemId = $items[0]['id'];
        $item = Inventory::getById($itemId);
        
        if (!$item) {
            echo "✗ getById() returned null for item ID: $itemId\n\n";
            exit(1);
        }
        
        $hasQuantity = array_key_exists('quantity', $item);
        $hasAvailableQuantity = array_key_exists('available_quantity', $item);
        
        if ($hasQuantity && $hasAvailableQuantity) {
            echo "✓ Both 'quantity' and 'available_quantity' fields are present\n";
            echo "  Item: {$item['name']} (ID: {$item['id']})\n";
            echo "  - quantity: {$item['quantity']}\n";
            echo "  - available_quantity: {$item['available_quantity']}\n";
            echo "  - current_stock: {$item['current_stock']}\n\n";
        } else {
            if (!$hasQuantity) echo "✗ Field 'quantity' is missing\n";
            if (!$hasAvailableQuantity) echo "✗ Field 'available_quantity' is missing\n";
            echo "\n";
            exit(1);
        }
    } else {
        echo "⚠ Skipping getById() test (no items in database)\n\n";
    }
    
    // Test 3: Verify quantity calculation logic
    echo "Test 3: Verify quantity equals current_stock\n";
    if (!empty($items)) {
        $allMatch = true;
        foreach ($items as $item) {
            if ((int)$item['quantity'] !== (int)$item['current_stock']) {
                echo "✗ Mismatch for item '{$item['name']}': quantity={$item['quantity']}, current_stock={$item['current_stock']}\n";
                $allMatch = false;
            }
        }
        if ($allMatch) {
            echo "✓ All items have quantity equal to current_stock\n\n";
        } else {
            echo "\n";
            exit(1);
        }
    } else {
        echo "⚠ Skipping test (no items in database)\n\n";
    }
    
    // Test 4: Verify available_quantity is less than or equal to quantity
    echo "Test 4: Verify available_quantity <= quantity\n";
    if (!empty($items)) {
        $allValid = true;
        foreach ($items as $item) {
            if ((int)$item['available_quantity'] > (int)$item['quantity']) {
                echo "✗ Invalid for item '{$item['name']}': available_quantity={$item['available_quantity']} > quantity={$item['quantity']}\n";
                $allValid = false;
            }
        }
        if ($allValid) {
            echo "✓ All items have valid available_quantity (≤ quantity)\n\n";
        } else {
            echo "\n";
            exit(1);
        }
    } else {
        echo "⚠ Skipping test (no items in database)\n\n";
    }
    
    echo "=== All Tests Passed ✓ ===\n";
    exit(0);
    
} catch (Exception $e) {
    echo "✗ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
