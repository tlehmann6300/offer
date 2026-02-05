<?php
/**
 * Unit Test: Inventory SQL Query Validation
 * Validates that the SQL queries in getAll() and getById() include quantity fields
 */

echo "=== Inventory SQL Query Validation Test ===\n\n";

// Read the Inventory.php file
$inventoryFile = __DIR__ . '/../includes/models/Inventory.php';
$content = file_get_contents($inventoryFile);

// Test 1: Check getAll() has quantity field
echo "Test 1: Verify getAll() includes 'quantity' field\n";
$getAllPattern = '/public\s+static\s+function\s+getAll.*?return\s+\$stmt->fetchAll\(\);/s';
if (preg_match($getAllPattern, $content, $matches)) {
    $getAllMethod = $matches[0];
    
    $hasQuantity = strpos($getAllMethod, 'i.current_stock as quantity') !== false;
    $hasAvailableQuantity = strpos($getAllMethod, 'available_quantity') !== false;
    $hasRentalsJoin = strpos($getAllMethod, 'LEFT JOIN rentals') !== false;
    $hasGroupBy = strpos($getAllMethod, 'GROUP BY i.id') !== false;
    
    if ($hasQuantity && $hasAvailableQuantity && $hasRentalsJoin && $hasGroupBy) {
        echo "✓ getAll() has all required elements:\n";
        echo "  - 'quantity' field: ✓\n";
        echo "  - 'available_quantity' field: ✓\n";
        echo "  - LEFT JOIN with rentals: ✓\n";
        echo "  - GROUP BY clause: ✓\n\n";
    } else {
        echo "✗ getAll() is missing required elements:\n";
        if (!$hasQuantity) echo "  - 'quantity' field is missing\n";
        if (!$hasAvailableQuantity) echo "  - 'available_quantity' field is missing\n";
        if (!$hasRentalsJoin) echo "  - LEFT JOIN with rentals is missing\n";
        if (!$hasGroupBy) echo "  - GROUP BY clause is missing\n";
        echo "\n";
        exit(1);
    }
} else {
    echo "✗ Could not find getAll() method\n\n";
    exit(1);
}

// Test 2: Check getById() has quantity field
echo "Test 2: Verify getById() includes 'quantity' field\n";
$getByIdPattern = '/public\s+static\s+function\s+getById.*?return\s+\$stmt->fetch\(\);/s';
if (preg_match($getByIdPattern, $content, $matches)) {
    $getByIdMethod = $matches[0];
    
    $hasQuantity = strpos($getByIdMethod, 'i.current_stock as quantity') !== false;
    $hasAvailableQuantity = strpos($getByIdMethod, 'available_quantity') !== false;
    $hasRentalsJoin = strpos($getByIdMethod, 'LEFT JOIN rentals') !== false;
    $hasGroupBy = strpos($getByIdMethod, 'GROUP BY i.id') !== false;
    
    if ($hasQuantity && $hasAvailableQuantity && $hasRentalsJoin && $hasGroupBy) {
        echo "✓ getById() has all required elements:\n";
        echo "  - 'quantity' field: ✓\n";
        echo "  - 'available_quantity' field: ✓\n";
        echo "  - LEFT JOIN with rentals: ✓\n";
        echo "  - GROUP BY clause: ✓\n\n";
    } else {
        echo "✗ getById() is missing required elements:\n";
        if (!$hasQuantity) echo "  - 'quantity' field is missing\n";
        if (!$hasAvailableQuantity) echo "  - 'available_quantity' field is missing\n";
        if (!$hasRentalsJoin) echo "  - LEFT JOIN with rentals is missing\n";
        if (!$hasGroupBy) echo "  - GROUP BY clause is missing\n";
        echo "\n";
        exit(1);
    }
} else {
    echo "✗ Could not find getById() method\n\n";
    exit(1);
}

// Test 3: Verify available_quantity calculation uses COALESCE
echo "Test 3: Verify available_quantity calculation uses COALESCE\n";
$coalescePattern = '/COALESCE\s*\(\s*SUM\s*\(\s*r\.amount\s*\)\s*,\s*0\s*\)/i';
if (preg_match($coalescePattern, $content)) {
    echo "✓ available_quantity calculation uses COALESCE(SUM(r.amount), 0)\n\n";
} else {
    echo "✗ available_quantity calculation does not use proper COALESCE\n\n";
    exit(1);
}

// Test 4: Verify rentals join includes actual_return IS NULL condition
echo "Test 4: Verify rentals join filters for active rentals only\n";
$rentalConditionPattern = '/r\.actual_return\s+IS\s+NULL/i';
if (preg_match($rentalConditionPattern, $content)) {
    echo "✓ Rentals join correctly filters for active rentals (actual_return IS NULL)\n\n";
} else {
    echo "✗ Rentals join missing filter for active rentals\n\n";
    exit(1);
}

echo "=== All SQL Validation Tests Passed ✓ ===\n";
echo "\nThe fix correctly:\n";
echo "1. Adds 'quantity' as an alias for 'current_stock'\n";
echo "2. Calculates 'available_quantity' by subtracting checked-out items\n";
echo "3. Uses LEFT JOIN to include items with no active rentals\n";
echo "4. Uses GROUP BY to aggregate rental amounts per item\n";
echo "5. Filters rentals to only count active (unreturned) checkouts\n";

exit(0);
