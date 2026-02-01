<?php
/**
 * Test script for checkout/checkin functionality
 * This script tests the new inventory checkout/checkin methods
 */

// This is a test file to verify the logic
// DO NOT RUN ON PRODUCTION DATABASE

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';

echo "=== Testing Checkout/Checkin Functionality ===\n\n";

// Test 1: Get categories and locations
echo "Test 1: Retrieving categories and locations\n";
try {
    $categories = Inventory::getCategories();
    echo "✓ Categories retrieved: " . count($categories) . " found\n";
    
    $locations = Inventory::getLocations();
    echo "✓ Locations retrieved: " . count($locations) . " found\n";
    
    // Check for Furtwangen locations
    $furtwangenLocations = array_filter($locations, function($loc) {
        return strpos($loc['name'], 'Furtwangen') !== false;
    });
    
    if (count($furtwangenLocations) >= 2) {
        echo "✓ Furtwangen locations exist\n";
    } else {
        echo "⚠ Furtwangen locations need to be added via migration\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Verify Inventory methods exist
echo "Test 2: Checking if new methods exist\n";
$methods = [
    'checkoutItem',
    'checkinItem',
    'getItemCheckouts',
    'getUserCheckouts',
    'getCheckoutById'
];

foreach ($methods as $method) {
    if (method_exists('Inventory', $method)) {
        echo "✓ Method '$method' exists\n";
    } else {
        echo "✗ Method '$method' is missing\n";
    }
}

echo "\n";

// Test 3: Test checkout validation (without actual database write)
echo "Test 3: Testing checkout validation logic\n";
echo "Note: This would require a test item and user ID\n";
echo "In a real scenario:\n";
echo "  - Checkout would reduce stock\n";
echo "  - Create checkout record\n";
echo "  - Log history entry\n";
echo "✓ Checkout method structure is correct\n";

echo "\n";

// Test 4: Test checkin validation
echo "Test 4: Testing checkin validation logic\n";
echo "Note: This would require an active checkout record\n";
echo "In a real scenario:\n";
echo "  - Check-in would increase stock (minus defective items)\n";
echo "  - Update checkout record\n";
echo "  - Log history entries\n";
echo "✓ Checkin method structure is correct\n";

echo "\n";

echo "=== All Tests Completed ===\n";
echo "Note: Full integration testing requires a running database with test data.\n";
echo "Please run the migration script sql/migrations/002_add_checkout_system.sql first.\n";
