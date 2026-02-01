<?php
/**
 * Test script for dashboard statistics functionality
 * This script tests the new dashboard methods for board/managers
 */

// This is a test file to verify the logic
// DO NOT RUN ON PRODUCTION DATABASE

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';

echo "=== Testing Dashboard Statistics Functionality ===\n\n";

// Test 1: Verify new methods exist
echo "Test 1: Checking if new methods exist\n";
$methods = [
    'getInStockStats',
    'getCheckedOutStats',
    'getWriteOffStatsThisMonth'
];

foreach ($methods as $method) {
    if (method_exists('Inventory', $method)) {
        echo "✓ Method '$method' exists\n";
    } else {
        echo "✗ Method '$method' is missing\n";
    }
}

echo "\n";

// Test 2: Test getInStockStats
echo "Test 2: Testing getInStockStats()\n";
try {
    $inStockStats = Inventory::getInStockStats();
    echo "✓ In-stock statistics retrieved successfully\n";
    echo "  - Total in stock: " . $inStockStats['total_in_stock'] . " units\n";
    echo "  - Unique items in stock: " . $inStockStats['unique_items_in_stock'] . " items\n";
    echo "  - Total value in stock: €" . number_format($inStockStats['total_value_in_stock'], 2) . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test getCheckedOutStats
echo "Test 3: Testing getCheckedOutStats()\n";
try {
    $checkedOutStats = Inventory::getCheckedOutStats();
    echo "✓ Checked-out statistics retrieved successfully\n";
    echo "  - Total checkouts: " . $checkedOutStats['total_checked_out'] . "\n";
    echo "  - Total quantity out: " . $checkedOutStats['total_quantity_out'] . " units\n";
    
    if ($checkedOutStats['total_checked_out'] > 0) {
        echo "  - Sample checkout details:\n";
        $firstCheckout = $checkedOutStats['checkouts'][0];
        echo "    Item: " . $firstCheckout['item_name'] . "\n";
        echo "    Quantity: " . $firstCheckout['quantity'] . "\n";
        echo "    Borrower: " . $firstCheckout['borrower_email'] . "\n";
        echo "    Destination: " . ($firstCheckout['destination'] ?? 'Not specified') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Test getWriteOffStatsThisMonth
echo "Test 4: Testing getWriteOffStatsThisMonth()\n";
try {
    $writeOffStats = Inventory::getWriteOffStatsThisMonth();
    echo "✓ Write-off statistics retrieved successfully\n";
    echo "  - Total write-offs this month: " . $writeOffStats['total_writeoffs'] . "\n";
    echo "  - Total quantity lost: " . $writeOffStats['total_quantity_lost'] . " units\n";
    
    if ($writeOffStats['total_writeoffs'] > 0) {
        echo "  - Sample write-off details:\n";
        $firstWriteOff = $writeOffStats['writeoffs'][0];
        echo "    Item: " . $firstWriteOff['item_name'] . "\n";
        echo "    Quantity: " . abs($firstWriteOff['change_amount']) . "\n";
        echo "    Reported by: " . $firstWriteOff['reported_by_email'] . "\n";
        echo "    Reason: " . ($firstWriteOff['comment'] ?? $firstWriteOff['reason'] ?? 'Not specified') . "\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

echo "=== All Tests Completed ===\n";
echo "Note: Full integration testing requires a running database with test data.\n";
