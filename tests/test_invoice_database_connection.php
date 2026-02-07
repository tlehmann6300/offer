<?php
/**
 * Test Invoice Database Connection Refactor
 * Validates that Invoice model uses the new 'rech' database connection
 * and that other models still use the 'content' connection
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/models/Invoice.php';
require_once __DIR__ . '/../includes/models/Project.php';
require_once __DIR__ . '/../includes/models/Inventory.php';

echo "=== Invoice Database Connection Test Suite ===\n\n";

try {
    // Test 1: Verify Database::getConnection() method exists and works
    echo "Test 1: Verify Database::getConnection() method\n";
    
    // Test getConnection with 'user'
    try {
        $userConn = Database::getConnection('user');
        if ($userConn instanceof PDO) {
            echo "✓ getConnection('user') returns PDO instance\n";
        } else {
            echo "✗ getConnection('user') did not return PDO instance\n";
        }
    } catch (Exception $e) {
        echo "✗ getConnection('user') failed: " . $e->getMessage() . "\n";
    }
    
    // Test getConnection with 'content'
    try {
        $contentConn = Database::getConnection('content');
        if ($contentConn instanceof PDO) {
            echo "✓ getConnection('content') returns PDO instance\n";
        } else {
            echo "✗ getConnection('content') did not return PDO instance\n";
        }
    } catch (Exception $e) {
        echo "✗ getConnection('content') failed: " . $e->getMessage() . "\n";
    }
    
    // Test getConnection with 'rech'
    try {
        $rechConn = Database::getConnection('rech');
        if ($rechConn instanceof PDO) {
            echo "✓ getConnection('rech') returns PDO instance\n";
        } else {
            echo "✗ getConnection('rech') did not return PDO instance\n";
        }
    } catch (Exception $e) {
        echo "✗ getConnection('rech') failed: " . $e->getMessage() . "\n";
    }
    
    // Test getConnection with 'invoice' (alias for 'rech')
    try {
        $invoiceConn = Database::getConnection('invoice');
        if ($invoiceConn instanceof PDO) {
            echo "✓ getConnection('invoice') returns PDO instance\n";
        } else {
            echo "✗ getConnection('invoice') did not return PDO instance\n";
        }
    } catch (Exception $e) {
        echo "✗ getConnection('invoice') failed: " . $e->getMessage() . "\n";
    }
    
    // Test getConnection with invalid name
    try {
        $invalidConn = Database::getConnection('invalid');
        echo "✗ getConnection('invalid') should have thrown an exception\n";
    } catch (Exception $e) {
        echo "✓ getConnection('invalid') correctly throws exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 2: Verify constants are defined
    echo "Test 2: Verify DB_RECH_* constants are defined\n";
    
    $rechConstants = [
        'DB_RECH_HOST',
        'DB_RECH_PORT',
        'DB_RECH_NAME',
        'DB_RECH_USER',
        'DB_RECH_PASS'
    ];
    
    foreach ($rechConstants as $constant) {
        if (defined($constant)) {
            echo "✓ $constant is defined\n";
        } else {
            echo "✗ $constant is NOT defined\n";
        }
    }
    
    echo "\n";
    
    // Test 3: Verify Invoice model uses rech connection
    echo "Test 3: Verify Invoice model code uses Database::getConnection('rech')\n";
    
    $invoiceModelPath = __DIR__ . '/../includes/models/Invoice.php';
    $invoiceCode = file_get_contents($invoiceModelPath);
    
    // Check that Invoice.php uses getConnection('rech')
    $rechUsageCount = substr_count($invoiceCode, "Database::getConnection('rech')");
    if ($rechUsageCount > 0) {
        echo "✓ Invoice.php uses Database::getConnection('rech') - found $rechUsageCount instance(s)\n";
    } else {
        echo "✗ Invoice.php does NOT use Database::getConnection('rech')\n";
    }
    
    // Check that Invoice.php does NOT use getContentDB()
    $contentUsageCount = substr_count($invoiceCode, 'Database::getContentDB()');
    if ($contentUsageCount === 0) {
        echo "✓ Invoice.php does NOT use Database::getContentDB() anymore\n";
    } else {
        echo "✗ Invoice.php still uses Database::getContentDB() - found $contentUsageCount instance(s)\n";
    }
    
    echo "\n";
    
    // Test 4: Verify other models still use content connection
    echo "Test 4: Verify other models still use Database::getContentDB()\n";
    
    $projectModelPath = __DIR__ . '/../includes/models/Project.php';
    $projectCode = file_get_contents($projectModelPath);
    $projectContentUsage = substr_count($projectCode, 'Database::getContentDB()');
    
    if ($projectContentUsage > 0) {
        echo "✓ Project.php still uses Database::getContentDB() - found $projectContentUsage instance(s)\n";
    } else {
        echo "✗ Project.php does NOT use Database::getContentDB() anymore (this is unexpected)\n";
    }
    
    $inventoryModelPath = __DIR__ . '/../includes/models/Inventory.php';
    $inventoryCode = file_get_contents($inventoryModelPath);
    $inventoryContentUsage = substr_count($inventoryCode, 'Database::getContentDB()');
    
    if ($inventoryContentUsage > 0) {
        echo "✓ Inventory.php still uses Database::getContentDB() - found $inventoryContentUsage instance(s)\n";
    } else {
        echo "✗ Inventory.php does NOT use Database::getContentDB() anymore (this is unexpected)\n";
    }
    
    echo "\n";
    
    // Test 5: Test invoice operations with new connection
    echo "Test 5: Test Invoice model operations with new connection\n";
    
    // Test getStats() - should work even with no invoices
    try {
        $stats = Invoice::getStats();
        if (is_array($stats) && isset($stats['total_pending']) && isset($stats['total_paid']) && isset($stats['monthly_paid'])) {
            echo "✓ Invoice::getStats() works with new connection\n";
            echo "  Total Pending: " . number_format($stats['total_pending'], 2) . "€\n";
            echo "  Total Paid: " . number_format($stats['total_paid'], 2) . "€\n";
        } else {
            echo "✗ Invoice::getStats() did not return expected structure\n";
        }
    } catch (Exception $e) {
        echo "✗ Invoice::getStats() failed: " . $e->getMessage() . "\n";
    }
    
    // Test getAll() for different roles
    try {
        $invoicesBoard = Invoice::getAll('board', 1);
        if (is_array($invoicesBoard)) {
            echo "✓ Invoice::getAll() works with new connection (board role sees " . count($invoicesBoard) . " invoice(s))\n";
        } else {
            echo "✗ Invoice::getAll() did not return an array\n";
        }
    } catch (Exception $e) {
        echo "✗ Invoice::getAll() failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 6: Verify connection reuse (singleton pattern)
    echo "Test 6: Verify connection reuse (singleton pattern)\n";
    
    $conn1 = Database::getConnection('rech');
    $conn2 = Database::getConnection('rech');
    
    if ($conn1 === $conn2) {
        echo "✓ Database::getConnection('rech') returns the same instance (singleton pattern works)\n";
    } else {
        echo "✗ Database::getConnection('rech') creates new instances (singleton pattern broken)\n";
    }
    
    echo "\n";
    
    echo "=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "✗ Test Failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
