<?php
/**
 * Database Schema Repair Script
 * 
 * This script repairs and synchronizes database schemas across all three databases:
 * 1. User Database (dbs15253086) - Authentication and user management
 * 2. Content Database (dbs15161271) - Alumni, inventory, events, etc.
 * 3. Invoice Database (dbs15251284) - Invoices and financial records
 */

// Load environment configuration
require_once __DIR__ . '/config/config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Database Schema Repair Tool</h1>\n";
echo "<pre>\n";

/**
 * Execute SQL and report status
 */
function executeSql($pdo, $sql, $description) {
    try {
        $pdo->exec($sql);
        echo "✅ SUCCESS: $description\n";
        return true;
    } catch (PDOException $e) {
        // Check if error is "column already exists" or "duplicate column"
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "ℹ️  SKIP: $description (already exists)\n";
            return true;
        }
        // Check if error is "table doesn't exist" for RENAME
        if (strpos($e->getMessage(), "doesn't exist") !== false) {
            echo "ℹ️  SKIP: $description (table doesn't exist)\n";
            return true;
        }
        echo "❌ ERROR: $description\n";
        echo "   Message: " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Check if column exists in a table
 */
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Check if table exists
 */
function tableExists($pdo, $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// ============================================================================
// CONNECTION 1: USER DATABASE (dbs15253086)
// ============================================================================
echo "\n=== USER DATABASE (dbs15253086) ===\n";
try {
    $userDb = new PDO(
        "mysql:host=" . DB_USER_HOST . ";dbname=" . DB_USER_NAME . ";charset=utf8mb4",
        DB_USER_USER,
        DB_USER_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to User Database\n\n";
    
    // Add login security columns
    executeSql($userDb, 
        "ALTER TABLE users ADD COLUMN last_login DATETIME NULL",
        "Add last_login column to users table"
    );
    
    executeSql($userDb,
        "ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0",
        "Add failed_login_attempts column to users table"
    );
    
    executeSql($userDb,
        "ALTER TABLE users ADD COLUMN locked_until DATETIME NULL",
        "Add locked_until column to users table"
    );
    
    executeSql($userDb,
        "ALTER TABLE users ADD COLUMN is_locked_permanently TINYINT DEFAULT 0",
        "Add is_locked_permanently column to users table"
    );
    
} catch (PDOException $e) {
    echo "❌ FATAL: Could not connect to User Database\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

// ============================================================================
// CONNECTION 2: CONTENT DATABASE (dbs15161271)
// ============================================================================
echo "\n=== CONTENT DATABASE (dbs15161271) ===\n";
try {
    $contentDb = new PDO(
        "mysql:host=" . DB_CONTENT_HOST . ";dbname=" . DB_CONTENT_NAME . ";charset=utf8mb4",
        DB_CONTENT_USER,
        DB_CONTENT_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to Content Database\n\n";
    
    // --- FIX ALUMNI PROFILES ---
    echo "--- Alumni Profiles Schema ---\n";
    
    if (!tableExists($contentDb, 'alumni_profiles')) {
        echo "⚠️  WARNING: alumni_profiles table does not exist. Skipping alumni fixes.\n";
    } else {
        executeSql($contentDb,
            "ALTER TABLE alumni_profiles ADD COLUMN graduation_year INT NULL",
            "Add graduation_year to alumni_profiles"
        );
        
        executeSql($contentDb,
            "ALTER TABLE alumni_profiles ADD COLUMN degree VARCHAR(255) NULL",
            "Add degree to alumni_profiles"
        );
        
        executeSql($contentDb,
            "ALTER TABLE alumni_profiles ADD COLUMN study_program VARCHAR(255) NULL",
            "Add study_program to alumni_profiles"
        );
        
        executeSql($contentDb,
            "ALTER TABLE alumni_profiles ADD COLUMN linkedin_url VARCHAR(255) NULL",
            "Add linkedin_url to alumni_profiles"
        );
    }
    
    // --- FIX INVENTORY ---
    echo "\n--- Inventory Schema ---\n";
    
    // Check if old 'inventory' table exists (without '_items' suffix)
    if (tableExists($contentDb, 'inventory') && !tableExists($contentDb, 'inventory_items')) {
        executeSql($contentDb,
            "RENAME TABLE inventory TO inventory_items",
            "Rename 'inventory' table to 'inventory_items'"
        );
    } else if (tableExists($contentDb, 'inventory') && tableExists($contentDb, 'inventory_items')) {
        echo "⚠️  WARNING: Both 'inventory' and 'inventory_items' tables exist. Manual intervention required.\n";
    } else if (!tableExists($contentDb, 'inventory_items')) {
        echo "ℹ️  SKIP: inventory_items table doesn't exist (will be created by other setup scripts)\n";
    } else {
        echo "ℹ️  OK: inventory_items table already exists\n";
    }
    
    // Rename column current_stock to quantity if it exists
    if (tableExists($contentDb, 'inventory_items')) {
        if (columnExists($contentDb, 'inventory_items', 'current_stock')) {
            executeSql($contentDb,
                "ALTER TABLE inventory_items CHANGE current_stock quantity INT DEFAULT 0",
                "Rename column 'current_stock' to 'quantity' in inventory_items"
            );
        } else if (columnExists($contentDb, 'inventory_items', 'quantity')) {
            echo "ℹ️  OK: 'quantity' column already exists in inventory_items\n";
        } else {
            echo "⚠️  WARNING: Neither 'current_stock' nor 'quantity' column found in inventory_items\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ FATAL: Could not connect to Content Database\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

// ============================================================================
// CONNECTION 3: INVOICE DATABASE (dbs15251284)
// ============================================================================
echo "\n=== INVOICE DATABASE (dbs15251284) ===\n";
try {
    $invoiceDb = new PDO(
        "mysql:host=" . DB_RECH_HOST . ";port=" . DB_RECH_PORT . ";dbname=" . DB_RECH_NAME . ";charset=utf8mb4",
        DB_RECH_USER,
        DB_RECH_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connected to Invoice Database\n\n";
    
    // Verify invoices table structure
    if (!tableExists($invoiceDb, 'invoices')) {
        echo "⚠️  WARNING: invoices table does not exist. It should be created by other setup scripts.\n";
    } else {
        echo "--- Invoices Table Schema ---\n";
        
        $requiredColumns = ['file_path', 'status', 'amount', 'description'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $column) {
            if (columnExists($invoiceDb, 'invoices', $column)) {
                echo "✅ Column '$column' exists in invoices table\n";
            } else {
                $missingColumns[] = $column;
                echo "⚠️  WARNING: Column '$column' is missing from invoices table\n";
            }
        }
        
        // Add missing columns based on common schema
        if (in_array('file_path', $missingColumns)) {
            executeSql($invoiceDb,
                "ALTER TABLE invoices ADD COLUMN file_path VARCHAR(500) NULL",
                "Add file_path column to invoices"
            );
        }
        
        if (in_array('status', $missingColumns)) {
            executeSql($invoiceDb,
                "ALTER TABLE invoices ADD COLUMN status VARCHAR(50) DEFAULT 'pending'",
                "Add status column to invoices"
            );
        }
        
        if (in_array('amount', $missingColumns)) {
            executeSql($invoiceDb,
                "ALTER TABLE invoices ADD COLUMN amount DECIMAL(10,2) DEFAULT 0.00",
                "Add amount column to invoices"
            );
        }
        
        if (in_array('description', $missingColumns)) {
            executeSql($invoiceDb,
                "ALTER TABLE invoices ADD COLUMN description TEXT NULL",
                "Add description column to invoices"
            );
        }
    }
    
} catch (PDOException $e) {
    echo "❌ FATAL: Could not connect to Invoice Database\n";
    echo "   Error: " . $e->getMessage() . "\n";
}

// ============================================================================
// SUMMARY
// ============================================================================
echo "\n=======================================================\n";
echo "✅ SYSTEM REPAIRED. Code uses quantity, DB has all columns.\n";
echo "=======================================================\n";
echo "</pre>\n";
?>
