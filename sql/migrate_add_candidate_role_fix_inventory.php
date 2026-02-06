<?php
/**
 * Migration Script: Add Candidate Role and Fix Inventory Table
 * 
 * This migration:
 * 1. Adds 'candidate' role to users and invitation_tokens tables
 * 2. Fixes inventory table structure to match easyVerein integration requirements
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
            MODIFY COLUMN role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni', 'candidate') 
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
            MODIFY COLUMN role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni', 'candidate') 
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
    
    // Check if inventory table exists
    $stmt = $contentDB->query("SHOW TABLES LIKE 'inventory'");
    $inventoryExists = $stmt->fetch() !== false;
    
    if (!$inventoryExists) {
        echo "Creating new inventory table with correct structure...\n";
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
        echo "Inventory table exists, checking and updating structure...\n";
        
        // Get current columns
        $stmt = $contentDB->query("SHOW COLUMNS FROM inventory");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Check easyverein_id column type
        $easyvereinIdColumn = null;
        foreach ($columns as $col) {
            if ($col['Field'] === 'easyverein_id') {
                $easyvereinIdColumn = $col;
                break;
            }
        }
        
        // Fix easyverein_id column if needed
        if ($easyvereinIdColumn) {
            // Check if it's VARCHAR instead of INT UNSIGNED
            if (strpos($easyvereinIdColumn['Type'], 'varchar') !== false) {
                echo "  - Converting easyverein_id from VARCHAR to INT UNSIGNED...\n";
                
                // First, remove the UNIQUE constraint if it exists
                $contentDB->exec("ALTER TABLE inventory DROP INDEX idx_easyverein_id");
                
                // Change column type
                $contentDB->exec("
                    ALTER TABLE inventory 
                    MODIFY COLUMN easyverein_id INT UNSIGNED NOT NULL
                ");
                
                // Re-add UNIQUE index
                $contentDB->exec("
                    ALTER TABLE inventory 
                    ADD UNIQUE INDEX idx_easyverein_id (easyverein_id)
                ");
                
                echo "    ✓ Successfully converted easyverein_id column\n";
            }
        } else {
            echo "  - Adding easyverein_id column...\n";
            $contentDB->exec("
                ALTER TABLE inventory 
                ADD COLUMN easyverein_id INT UNSIGNED NOT NULL UNIQUE AFTER id,
                ADD INDEX idx_easyverein_id (easyverein_id)
            ");
            echo "    ✓ Added easyverein_id column\n";
        }
        
        // Check and add missing columns
        $requiredColumns = [
            'image_path' => "ALTER TABLE inventory ADD COLUMN image_path VARCHAR(255) DEFAULT NULL",
            'acquisition_date' => "ALTER TABLE inventory ADD COLUMN acquisition_date DATE DEFAULT NULL",
            'value' => "ALTER TABLE inventory ADD COLUMN value DECIMAL(10, 2) DEFAULT 0.00",
            'last_synced_at' => "ALTER TABLE inventory ADD COLUMN last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];
        
        foreach ($requiredColumns as $columnName => $alterSQL) {
            if (!in_array($columnName, $columnNames)) {
                echo "  - Adding missing column: {$columnName}...\n";
                $contentDB->exec($alterSQL);
                echo "    ✓ Added {$columnName} column\n";
            } else {
                echo "  - Column {$columnName} already exists\n";
            }
        }
        
        // Update name column length if needed
        $nameColumn = null;
        foreach ($columns as $col) {
            if ($col['Field'] === 'name') {
                $nameColumn = $col;
                break;
            }
        }
        
        if ($nameColumn && strpos($nameColumn['Type'], 'varchar(100)') !== false) {
            echo "  - Updating name column length to VARCHAR(255)...\n";
            $contentDB->exec("
                ALTER TABLE inventory 
                MODIFY COLUMN name VARCHAR(255) NOT NULL
            ");
            echo "    ✓ Updated name column length\n";
        }
        
        // Update location to VARCHAR(255) if it's a foreign key reference
        $locationColumn = null;
        foreach ($columns as $col) {
            if ($col['Field'] === 'location') {
                $locationColumn = $col;
                break;
            }
        }
        
        if ($locationColumn === null) {
            echo "  - Adding location column...\n";
            $contentDB->exec("
                ALTER TABLE inventory 
                ADD COLUMN location VARCHAR(255) DEFAULT NULL
            ");
            echo "    ✓ Added location column\n";
        }
        
        echo "✓ Inventory table structure updated successfully\n";
    }
    
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "✓ Migration completed successfully!\n";
    echo "\n";
    echo "Summary:\n";
    echo "  - Added 'candidate' role to users table\n";
    echo "  - Added 'candidate' role to invitation_tokens table\n";
    echo "  - Fixed inventory table structure for easyVerein integration\n";
    echo "  - Ensured all required columns exist\n";
    
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
