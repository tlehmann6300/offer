<?php
/**
 * Migration Script: Add Invoice Module
 * 
 * This migration adds support for an invoice management system:
 * 1. Modifies the users table role ENUM to include 'alumni_board'
 * 2. Creates the invoices table with all required fields for invoice tracking
 * 
 * New role list: 'admin', 'board', 'head', 'member', 'alumni', 'candidate', 'alumni_board'
 */

require_once __DIR__ . '/../includes/database.php';

try {
    echo "Starting migration: Add Invoice Module\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $userDB = Database::getUserDB();
    
    // ============================================
    // STEP 1: Add 'alumni_board' role to users table
    // ============================================
    echo "Step 1: Adding 'alumni_board' role to users table...\n";
    
    $stmt = $userDB->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn && strpos($roleColumn['Type'], "'alumni_board'") === false) {
        $userDB->exec("
            ALTER TABLE users 
            MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
            NOT NULL DEFAULT 'member'
        ");
        echo "✅ Role 'alumni_board' successfully added to users table\n";
    } else {
        echo "✅ Role 'alumni_board' already exists in users table\n";
    }
    
    echo "\n";
    
    // ============================================
    // STEP 1.5: Add 'alumni_board' role to user_invitations table
    // ============================================
    echo "Step 1.5: Adding 'alumni_board' role to user_invitations table...\n";
    
    $stmt = $userDB->query("SHOW COLUMNS FROM user_invitations LIKE 'role'");
    $invitationRoleColumn = $stmt->fetch();
    
    if ($invitationRoleColumn && strpos($invitationRoleColumn['Type'], "'alumni_board'") === false) {
        $userDB->exec("
            ALTER TABLE user_invitations 
            MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
            NOT NULL DEFAULT 'member'
        ");
        echo "✅ Role 'alumni_board' successfully added to user_invitations table\n";
    } else {
        echo "✅ Role 'alumni_board' already exists in user_invitations table\n";
    }
    
    echo "\n";
    
    // ============================================
    // STEP 2: Create invoices table
    // ============================================
    echo "Step 2: Creating invoices table...\n";
    
    // Check if table already exists
    $stmt = $userDB->query("SHOW TABLES LIKE 'invoices'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        $userDB->exec("
            CREATE TABLE invoices (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to users table',
                description VARCHAR(255) NOT NULL COMMENT 'Short purpose description',
                amount DECIMAL(10,2) NOT NULL COMMENT 'Invoice amount',
                date_of_receipt DATE NOT NULL COMMENT 'Date the receipt was received',
                file_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded receipt image/pdf',
                status ENUM('pending', 'approved', 'rejected', 'paid') 
                    NOT NULL DEFAULT 'pending' 
                    COMMENT 'Invoice processing status',
                rejection_reason TEXT DEFAULT NULL COMMENT 'Reason for rejection if applicable',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Invoice creation timestamp',
                
                -- Foreign key constraint
                CONSTRAINT fk_invoice_user
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                
                -- Indexes for performance
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_date_of_receipt (date_of_receipt),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB
              DEFAULT CHARSET=utf8mb4
              COLLATE=utf8mb4_unicode_ci
              COMMENT='Invoice management system for receipt tracking and approval'
        ");
        echo "✅ Invoices table successfully created\n";
    } else {
        echo "✅ Invoices table already exists\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Migration completed successfully!\n";
    echo "\n";
    echo "Summary of changes:\n";
    echo "- Added 'alumni_board' role to users table\n";
    echo "- Added 'alumni_board' role to user_invitations table\n";
    echo "- Created invoices table with all required fields\n";
    echo "\n";
    echo "Note: Remember to create the uploads/invoices/ directory manually\n";
    echo "      and add appropriate write permissions.\n";
    
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
