<?php
/**
 * Migration Script: Add Candidate Role and Fix Inventory Table
 * 
 * This migration:
 * 1. Adds 'candidate' role to users and invitation_tokens tables
 * 2. Updates role ENUM to: admin, board, head, member, alumni, candidate
 * 3. Fixes inventory/inventory_items table to ensure image_path column exists
 */

require_once __DIR__ . '/../includes/database.php';

try {
    echo "Starting migration: Add Candidate Role and Fix Inventory Table\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // ========================================
    // PART 1: Add 'candidate' role to ENUMs
    // ========================================
    
    echo "PART 1: Adding 'candidate' role to user tables\n";
    echo str_repeat('-', 60) . "\n";
    
    $userDB = Database::getUserDB();
    
    // Check if 'candidate' role already exists in users table
    $stmt = $userDB->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn && strpos($roleColumn['Type'], 'candidate') === false) {
        echo "Adding 'candidate' role to users table...\n";
        $userDB->exec("
            ALTER TABLE users 
            MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
            NOT NULL DEFAULT 'member'
        ");
        echo "✓ Successfully added 'candidate' role to users table\n";
    } else {
        echo "✓ 'candidate' role already exists in users table\n";
    }
    
    // Check if 'candidate' role already exists in invitation_tokens table
    $stmt = $userDB->query("SHOW COLUMNS FROM invitation_tokens LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn && strpos($roleColumn['Type'], 'candidate') === false) {
        echo "Adding 'candidate' role to invitation_tokens table...\n";
        $userDB->exec("
            ALTER TABLE invitation_tokens 
            MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
            NOT NULL DEFAULT 'member'
        ");
        echo "✓ Successfully added 'candidate' role to invitation_tokens table\n";
    } else {
        echo "✓ 'candidate' role already exists in invitation_tokens table\n";
    }
    
    echo "\n";
    
    // ========================================
    // PART 2: Fix inventory table structure
    // ========================================
    
    echo "PART 2: Fixing inventory table structure\n";
    echo str_repeat('-', 60) . "\n";
    
    $contentDB = Database::getContentDB();
    
    // Check which inventory table exists (inventory or inventory_items)
    $stmt = $contentDB->query("SHOW TABLES LIKE 'inventory'");
    $inventoryExists = $stmt->fetch() !== false;
    
    $stmt = $contentDB->query("SHOW TABLES LIKE 'inventory_items'");
    $inventoryItemsExists = $stmt->fetch() !== false;
    
    $tableToFix = null;
    if ($inventoryItemsExists) {
        $tableToFix = 'inventory_items';
        echo "Found 'inventory_items' table (full schema)\n";
    } elseif ($inventoryExists) {
        $tableToFix = 'inventory';
        echo "Found 'inventory' table (modern schema)\n";
    }
    
    if (!$tableToFix) {
        echo "⚠ Warning: Neither 'inventory' nor 'inventory_items' table found\n";
        echo "  Creating 'inventory' table with correct structure...\n";
        $contentDB->exec("
            CREATE TABLE inventory (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                easyverein_id INT UNSIGNED NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                serial_number VARCHAR(100),
                location VARCHAR(255),
                image_path VARCHAR(255) DEFAULT NULL,
                acquisition_date DATE DEFAULT NULL,
                value DECIMAL(10, 2) DEFAULT 0.00,
                last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_easyverein_id (easyverein_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "✓ Successfully created inventory table\n";
    } else {
        echo "Checking and updating {$tableToFix} table structure...\n";
        
        // Get current columns
        $stmt = $contentDB->query("SHOW COLUMNS FROM {$tableToFix}");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Check if image_path column exists
        if (!in_array('image_path', $columnNames)) {
            echo "  - Adding missing column: image_path...\n";
            $contentDB->exec("ALTER TABLE {$tableToFix} ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
            echo "    ✓ Added image_path column\n";
        } else {
            echo "  - Column image_path already exists\n";
        }
        
        echo "✓ {$tableToFix} table structure updated successfully\n";
    }
    
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "✓ Migration completed successfully!\n";
    echo "\n";
    echo "Summary:\n";
    echo "  - Added 'candidate' role to users table\n";
    echo "  - Added 'candidate' role to invitation_tokens table\n";
    if ($tableToFix) {
        echo "  - Fixed {$tableToFix} table structure\n";
        echo "  - Ensured image_path column exists\n";
    } else {
        echo "  - Created inventory table with correct structure\n";
    }
    
} catch (Exception $e) {
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
