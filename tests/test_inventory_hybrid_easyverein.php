<?php
/**
 * Test Inventory Hybrid EasyVerein Functionality
 * Tests the new methods and protection mechanisms for EasyVerein integration
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Inventory.php';
require_once __DIR__ . '/../includes/services/EasyVereinSync.php';

echo "=== Inventory Hybrid EasyVerein Test Suite ===\n\n";

// Test 1: Test getAvailableStock() method
echo "Test 1: Test getAvailableStock() method\n";
try {
    $db = Database::getContentDB();
    
    // Check if database is available
    $stmt = $db->query("SHOW TABLES LIKE 'inventory'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "⚠ Inventory table does not exist, skipping database tests\n\n";
    } else {
        // Create a test item
        $testUserId = 1;
        $testItemData = [
            'name' => 'Test Item for Available Stock',
            'description' => 'Test description',
            'current_stock' => 10,
            'min_stock' => 2,
            'unit' => 'Stück'
        ];
        
        $testItemId = Inventory::create($testItemData, $testUserId);
        
        // Check available stock (should be 10, no rentals yet)
        $availableStock = Inventory::getAvailableStock($testItemId);
        
        if ($availableStock === 10) {
            echo "✓ getAvailableStock() returns correct value with no rentals (10)\n";
        } else {
            echo "✗ getAvailableStock() returned $availableStock, expected 10\n";
        }
        
        // Create a rental (checkout 3 items)
        $checkoutResult = Inventory::checkoutItem($testItemId, $testUserId, 3, 'Testing', null, date('Y-m-d', strtotime('+7 days')));
        
        if ($checkoutResult['success']) {
            // Check available stock (should be 10 - 3 = 7)
            $availableStock = Inventory::getAvailableStock($testItemId);
            
            if ($availableStock === 7) {
                echo "✓ getAvailableStock() returns correct value with active rentals (7)\n";
            } else {
                echo "✗ getAvailableStock() returned $availableStock, expected 7\n";
            }
        } else {
            echo "⚠ Could not create rental for testing: {$checkoutResult['message']}\n";
        }
        
        // Clean up test item
        Inventory::delete($testItemId, $testUserId);
        echo "✓ Test item cleaned up\n\n";
    }
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Test update() protection for EasyVerein-synced items (Master Data)
echo "Test 2: Test update() protection for EasyVerein-synced items (Master Data)\n";
try {
    $db = Database::getContentDB();
    
    // Check if EasyVerein columns exist
    $stmt = $db->query("DESCRIBE inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('easyverein_id', $columnNames)) {
        echo "⚠ easyverein_id column does not exist, skipping test\n\n";
    } else {
        // Create a test item with easyverein_id
        $testUserId = 1;
        
        $stmt = $db->prepare("
            INSERT INTO inventory (name, description, current_stock, easyverein_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['Test EV Item', 'Test description', 5, 'EV-TEST-001']);
        $testItemId = $db->lastInsertId();
        
        // Try to update Master Data field (name) - should throw exception
        $exceptionThrown = false;
        try {
            Inventory::update($testItemId, ['name' => 'Modified Name'], $testUserId);
        } catch (Exception $e) {
            $exceptionThrown = true;
            if (strpos($e->getMessage(), 'Master Data') !== false) {
                echo "✓ Exception thrown when attempting to modify Master Data (name)\n";
            } else {
                echo "✗ Wrong exception message: " . $e->getMessage() . "\n";
            }
        }
        
        if (!$exceptionThrown) {
            echo "✗ No exception thrown when attempting to modify Master Data\n";
        }
        
        // Try to update Master Data field (description) - should throw exception
        $exceptionThrown = false;
        try {
            Inventory::update($testItemId, ['description' => 'Modified Description'], $testUserId);
        } catch (Exception $e) {
            $exceptionThrown = true;
            if (strpos($e->getMessage(), 'Master Data') !== false) {
                echo "✓ Exception thrown when attempting to modify Master Data (description)\n";
            } else {
                echo "✗ Wrong exception message: " . $e->getMessage() . "\n";
            }
        }
        
        if (!$exceptionThrown) {
            echo "✗ No exception thrown when attempting to modify Master Data\n";
        }
        
        // Try to update Master Data field (current_stock) - should throw exception
        $exceptionThrown = false;
        try {
            Inventory::update($testItemId, ['current_stock' => 10], $testUserId);
        } catch (Exception $e) {
            $exceptionThrown = true;
            if (strpos($e->getMessage(), 'Master Data') !== false) {
                echo "✓ Exception thrown when attempting to modify Master Data (current_stock)\n";
            } else {
                echo "✗ Wrong exception message: " . $e->getMessage() . "\n";
            }
        }
        
        if (!$exceptionThrown) {
            echo "✗ No exception thrown when attempting to modify Master Data\n";
        }
        
        // Clean up test item
        Inventory::delete($testItemId, $testUserId);
        echo "✓ Test item cleaned up\n\n";
    }
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Test update() allows Local Operational Data changes for EasyVerein-synced items
echo "Test 3: Test update() allows Local Operational Data changes for EasyVerein-synced items\n";
try {
    $db = Database::getContentDB();
    
    // Check if EasyVerein columns exist
    $stmt = $db->query("DESCRIBE inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('easyverein_id', $columnNames)) {
        echo "⚠ easyverein_id column does not exist, skipping test\n\n";
    } else {
        // Create a test item with easyverein_id
        $testUserId = 1;
        
        $stmt = $db->prepare("
            INSERT INTO inventory (name, description, current_stock, easyverein_id, notes)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute(['Test EV Item 2', 'Test description', 5, 'EV-TEST-002', 'Original notes']);
        $testItemId = $db->lastInsertId();
        
        // Try to update Local Operational Data field (notes) - should succeed
        try {
            Inventory::update($testItemId, ['notes' => 'Modified notes'], $testUserId);
            
            // Verify the update
            $stmt = $db->prepare("SELECT notes FROM inventory WHERE id = ?");
            $stmt->execute([$testItemId]);
            $item = $stmt->fetch();
            
            if ($item['notes'] === 'Modified notes') {
                echo "✓ Successfully updated Local Operational Data (notes)\n";
            } else {
                echo "✗ Local Operational Data not updated correctly\n";
            }
        } catch (Exception $e) {
            echo "✗ Unexpected exception when updating Local Operational Data: " . $e->getMessage() . "\n";
        }
        
        // Try to update Local Operational Data field (location_id) - should succeed
        try {
            // First, ensure there's a location
            $stmt = $db->prepare("SELECT id FROM locations LIMIT 1");
            $stmt->execute();
            $location = $stmt->fetch();
            
            if ($location) {
                $locationId = $location['id'];
                Inventory::update($testItemId, ['location_id' => $locationId], $testUserId);
                
                // Verify the update
                $stmt = $db->prepare("SELECT location_id FROM inventory WHERE id = ?");
                $stmt->execute([$testItemId]);
                $item = $stmt->fetch();
                
                if ($item['location_id'] == $locationId) {
                    echo "✓ Successfully updated Local Operational Data (location_id)\n";
                } else {
                    echo "✗ Local Operational Data not updated correctly\n";
                }
            } else {
                echo "⚠ No locations available to test location_id update\n";
            }
        } catch (Exception $e) {
            echo "✗ Unexpected exception when updating Local Operational Data: " . $e->getMessage() . "\n";
        }
        
        // Clean up test item
        Inventory::delete($testItemId, $testUserId);
        echo "✓ Test item cleaned up\n\n";
    }
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Test update() with $isSyncUpdate flag allows Master Data changes
echo "Test 4: Test update() with \$isSyncUpdate flag allows Master Data changes\n";
try {
    $db = Database::getContentDB();
    
    // Check if EasyVerein columns exist
    $stmt = $db->query("DESCRIBE inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('easyverein_id', $columnNames)) {
        echo "⚠ easyverein_id column does not exist, skipping test\n\n";
    } else {
        // Create a test item with easyverein_id
        $testUserId = 1;
        
        $stmt = $db->prepare("
            INSERT INTO inventory (name, description, current_stock, easyverein_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['Test EV Item 3', 'Test description', 5, 'EV-TEST-003']);
        $testItemId = $db->lastInsertId();
        
        // Try to update Master Data with $isSyncUpdate = true - should succeed
        try {
            Inventory::update($testItemId, ['name' => 'Synced Name', 'current_stock' => 15], $testUserId, true);
            
            // Verify the update
            $stmt = $db->prepare("SELECT name, current_stock FROM inventory WHERE id = ?");
            $stmt->execute([$testItemId]);
            $item = $stmt->fetch();
            
            if ($item['name'] === 'Synced Name' && $item['current_stock'] == 15) {
                echo "✓ Successfully updated Master Data with \$isSyncUpdate flag\n";
            } else {
                echo "✗ Master Data not updated correctly with \$isSyncUpdate flag\n";
            }
        } catch (Exception $e) {
            echo "✗ Unexpected exception when updating with \$isSyncUpdate flag: " . $e->getMessage() . "\n";
        }
        
        // Clean up test item
        Inventory::delete($testItemId, $testUserId);
        echo "✓ Test item cleaned up\n\n";
    }
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n\n";
}

// Test 5: Test syncFromEasyVerein() wrapper method
echo "Test 5: Test syncFromEasyVerein() wrapper method\n";
try {
    $db = Database::getContentDB();
    
    // Check if EasyVerein columns exist
    $stmt = $db->query("DESCRIBE inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    if (!in_array('easyverein_id', $columnNames)) {
        echo "⚠ easyverein_id column does not exist, skipping test\n\n";
    } else {
        $testUserId = 1;
        
        // Call the wrapper method
        $result = Inventory::syncFromEasyVerein($testUserId);
        
        // Check the result structure
        if (isset($result['created']) && isset($result['updated']) && isset($result['archived']) && isset($result['errors'])) {
            echo "✓ syncFromEasyVerein() returns correct structure\n";
            echo "  Created: {$result['created']}\n";
            echo "  Updated: {$result['updated']}\n";
            echo "  Archived: {$result['archived']}\n";
            echo "  Errors: " . count($result['errors']) . "\n";
            
            if (empty($result['errors'])) {
                echo "✓ syncFromEasyVerein() completed without errors\n";
            } else {
                echo "⚠ syncFromEasyVerein() completed with errors:\n";
                foreach ($result['errors'] as $error) {
                    echo "    - $error\n";
                }
            }
        } else {
            echo "✗ syncFromEasyVerein() returned incorrect structure\n";
        }
        
        echo "\n";
    }
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Test that non-EasyVerein items can still be updated normally
echo "Test 6: Test that non-EasyVerein items can still be updated normally\n";
try {
    $db = Database::getContentDB();
    
    // Create a test item WITHOUT easyverein_id
    $testUserId = 1;
    $testItemData = [
        'name' => 'Regular Item',
        'description' => 'Regular description',
        'current_stock' => 5,
        'min_stock' => 2,
        'unit' => 'Stück'
    ];
    
    $testItemId = Inventory::create($testItemData, $testUserId);
    
    // Try to update Master Data fields - should succeed
    try {
        Inventory::update($testItemId, ['name' => 'Updated Regular Item', 'current_stock' => 10], $testUserId);
        
        // Verify the update
        $stmt = $db->prepare("SELECT name, current_stock FROM inventory WHERE id = ?");
        $stmt->execute([$testItemId]);
        $item = $stmt->fetch();
        
        if ($item['name'] === 'Updated Regular Item' && $item['current_stock'] == 10) {
            echo "✓ Non-EasyVerein items can be updated normally\n";
        } else {
            echo "✗ Non-EasyVerein items not updated correctly\n";
        }
    } catch (Exception $e) {
        echo "✗ Unexpected exception when updating non-EasyVerein item: " . $e->getMessage() . "\n";
    }
    
    // Clean up test item
    Inventory::delete($testItemId, $testUserId);
    echo "✓ Test item cleaned up\n\n";
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n\n";
}

echo "=== Test Suite Complete ===\n";
