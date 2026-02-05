<?php
/**
 * Test EasyVereinSync Service
 * Tests the EasyVereinSync class functionality
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/services/EasyVereinSync.php';

echo "=== EasyVereinSync Test Suite ===\n\n";

// Test 1: Instantiate the class
echo "Test 1: Instantiate EasyVereinSync class\n";
try {
    $sync = new EasyVereinSync();
    echo "✓ EasyVereinSync class instantiated successfully\n\n";
} catch (Exception $e) {
    echo "✗ Failed to instantiate: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Fetch mock data from EasyVerein
echo "Test 2: Fetch mock data from EasyVerein\n";
try {
    $data = $sync->fetchDataFromEasyVerein();
    
    if (!is_array($data)) {
        echo "✗ Data is not an array\n\n";
    } elseif (empty($data)) {
        echo "✗ Data is empty\n\n";
    } else {
        echo "✓ Mock data fetched successfully\n";
        echo "  Items fetched: " . count($data) . "\n";
        
        // Validate structure of first item
        $firstItem = $data[0];
        $requiredFields = ['EasyVereinID', 'Name', 'Description', 'TotalQuantity', 'SerialNumber'];
        $valid = true;
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $firstItem)) {
                echo "✗ Missing required field: $field\n";
                $valid = false;
            }
        }
        
        if ($valid) {
            echo "✓ All required fields present in mock data\n";
            echo "  Sample item: {$firstItem['Name']} (EV-ID: {$firstItem['EasyVereinID']})\n\n";
        } else {
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Failed to fetch data: " . $e->getMessage() . "\n\n";
}

// Test 3: Validate mock data structure
echo "Test 3: Validate complete mock data structure\n";
try {
    $data = $sync->fetchDataFromEasyVerein();
    $valid = true;
    $requiredFields = ['EasyVereinID', 'Name', 'Description', 'TotalQuantity', 'SerialNumber'];
    
    foreach ($data as $index => $item) {
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $item)) {
                echo "✗ Item at index $index missing field: $field\n";
                $valid = false;
            }
        }
        
        // Validate data types
        if (!is_string($item['EasyVereinID'])) {
            echo "✗ Item at index $index: EasyVereinID should be string\n";
            $valid = false;
        }
        if (!is_string($item['Name'])) {
            echo "✗ Item at index $index: Name should be string\n";
            $valid = false;
        }
        if (!is_int($item['TotalQuantity']) && !is_numeric($item['TotalQuantity'])) {
            echo "✗ Item at index $index: TotalQuantity should be numeric\n";
            $valid = false;
        }
    }
    
    if ($valid) {
        echo "✓ All items have valid structure and data types\n\n";
    } else {
        echo "\n";
    }
} catch (Exception $e) {
    echo "✗ Failed to validate data: " . $e->getMessage() . "\n\n";
}

// Test 4: Test sync method (dry run - requires database connection)
echo "Test 4: Test sync method with database\n";
try {
    // Check if database is available
    $db = Database::getContentDB();
    
    // Test connection by checking if inventory table exists
    $stmt = $db->query("SHOW TABLES LIKE 'inventory'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "⚠ Inventory table does not exist, skipping database tests\n\n";
    } else {
        // Check if EasyVerein columns exist
        $stmt = $db->query("DESCRIBE inventory");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        $requiredColumns = ['easyverein_id', 'last_synced_at', 'is_archived_in_easyverein'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $col) {
            if (!in_array($col, $columnNames)) {
                $missingColumns[] = $col;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "⚠ Missing required columns: " . implode(', ', $missingColumns) . "\n";
            echo "  Please run apply_easyverein_migration.php first\n\n";
        } else {
            echo "✓ All required database columns exist\n";
            
            // Perform actual sync
            echo "  Running sync...\n";
            $result = $sync->sync(1); // Use user ID 1 for testing
            
            echo "  Created: {$result['created']}\n";
            echo "  Updated: {$result['updated']}\n";
            echo "  Archived: {$result['archived']}\n";
            
            if (!empty($result['errors'])) {
                echo "  Errors:\n";
                foreach ($result['errors'] as $error) {
                    echo "    - $error\n";
                }
            }
            
            if (empty($result['errors'])) {
                echo "✓ Sync completed successfully\n\n";
            } else {
                echo "⚠ Sync completed with errors\n\n";
            }
        }
    }
} catch (Exception $e) {
    echo "⚠ Database test skipped: " . $e->getMessage() . "\n\n";
}

// Test 5: Test sync preserves local fields (requires database)
echo "Test 5: Test that sync preserves local operational fields\n";
try {
    $db = Database::getContentDB();
    
    // Check if we can access the database
    $stmt = $db->query("SHOW TABLES LIKE 'inventory'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "⚠ Inventory table does not exist, skipping test\n\n";
    } else {
        // Create a test item with specific location and category
        $testItemId = null;
        $testEVId = 'EV-001'; // This matches first item in mock data
        
        // Check if test item already exists
        $stmt = $db->prepare("SELECT id, location_id, category_id FROM inventory WHERE easyverein_id = ?");
        $stmt->execute([$testEVId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            $testItemId = $existingItem['id'];
            $oldLocationId = $existingItem['location_id'];
            $oldCategoryId = $existingItem['category_id'];
            
            echo "  Test item exists with location_id=$oldLocationId, category_id=$oldCategoryId\n";
            
            // Run sync
            $result = $sync->sync(1);
            
            // Check if location_id and category_id are preserved
            $stmt = $db->prepare("SELECT location_id, category_id FROM inventory WHERE id = ?");
            $stmt->execute([$testItemId]);
            $updatedItem = $stmt->fetch();
            
            if ($updatedItem['location_id'] == $oldLocationId && $updatedItem['category_id'] == $oldCategoryId) {
                echo "✓ Local operational fields (location_id, category_id) preserved after sync\n\n";
            } else {
                echo "✗ Local operational fields were modified (should not happen)\n";
                echo "  Old: location_id=$oldLocationId, category_id=$oldCategoryId\n";
                echo "  New: location_id={$updatedItem['location_id']}, category_id={$updatedItem['category_id']}\n\n";
            }
        } else {
            echo "⚠ Test item doesn't exist yet, running sync to create it\n";
            $result = $sync->sync(1);
            echo "  Created: {$result['created']} items\n";
            echo "  Run this test again after items are created\n\n";
        }
    }
} catch (Exception $e) {
    echo "⚠ Test skipped: " . $e->getMessage() . "\n\n";
}

echo "=== Test Suite Complete ===\n";
