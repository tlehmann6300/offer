<?php
/**
 * Fix Schema and Roles Migration Script
 * 
 * This migration:
 * 1. Checks if inventory_items table has image_path column and adds it if missing
 * 2. Modifies users table role ENUM to include 'candidate'
 * 3. Checks if user_invitations table has expires_at (DATETIME) column and adds it if missing
 */

require_once __DIR__ . '/../includes/database.php';

try {
    echo "Starting migration: Fix Schema and Roles\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // ========================================
    // PART 1: Fix Inventory Items Table
    // ========================================
    
    echo "PART 1: Checking inventory_items table\n";
    echo str_repeat('-', 60) . "\n";
    
    $contentDB = Database::getContentDB();
    
    // Check if inventory_items table exists
    $stmt = $contentDB->query("SHOW TABLES LIKE 'inventory_items'");
    $inventoryItemsExists = $stmt->fetch() !== false;
    
    if ($inventoryItemsExists) {
        echo "Found 'inventory_items' table\n";
        
        // Check if image_path column exists
        $stmt = $contentDB->query("SHOW COLUMNS FROM inventory_items LIKE 'image_path'");
        $imagePathExists = $stmt->fetch() !== false;
        
        if (!$imagePathExists) {
            echo "Adding image_path column to inventory_items table...\n";
            $contentDB->exec("ALTER TABLE inventory_items ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
            echo "✓ Successfully added image_path column\n";
        } else {
            echo "✓ image_path column already exists\n";
        }
    } else {
        echo "⚠ inventory_items table not found (skipping)\n";
    }
    
    echo "\n";
    
    // ========================================
    // PART 2: Modify Users Table Role ENUM
    // ========================================
    
    echo "PART 2: Updating users table role ENUM\n";
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
    
    echo "\n";
    
    // ========================================
    // PART 3: Check/Add user_invitations Table with expires_at
    // ========================================
    
    echo "PART 3: Checking user_invitations table\n";
    echo str_repeat('-', 60) . "\n";
    
    // Check if user_invitations table exists
    $stmt = $userDB->query("SHOW TABLES LIKE 'user_invitations'");
    $userInvitationsExists = $stmt->fetch() !== false;
    
    if ($userInvitationsExists) {
        echo "Found 'user_invitations' table\n";
        
        // Check if expires_at column exists
        $stmt = $userDB->query("SHOW COLUMNS FROM user_invitations LIKE 'expires_at'");
        $expiresAtExists = $stmt->fetch() !== false;
        
        if (!$expiresAtExists) {
            echo "Adding expires_at column to user_invitations table...\n";
            $userDB->exec("ALTER TABLE user_invitations ADD COLUMN expires_at DATETIME DEFAULT NULL");
            echo "✓ Successfully added expires_at column\n";
        } else {
            echo "✓ expires_at column already exists\n";
        }
    } else {
        echo "⚠ user_invitations table not found (skipping)\n";
    }
    
    echo "\n";
    echo str_repeat('=', 60) . "\n";
    echo "✅ Database Schema fixed and Roles updated\n";
    
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
