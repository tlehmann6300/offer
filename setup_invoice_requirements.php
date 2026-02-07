<?php
/**
 * Setup Invoice Requirements Script
 * This script fixes the environment for the Invoice Module
 * 
 * Features:
 * - Database Fix: Adds 'alumni_board' role to users table
 * - Directory Fix: Creates uploads/invoices with proper permissions
 * - Security: Adds protection against directory listing
 */

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

try {
    echo "Starting Invoice Module Environment Setup\n";
    echo str_repeat('=', 60) . "\n\n";
    
    // ============================================
    // PART 1: Database Fix (Role)
    // ============================================
    echo "Part 1: Database Role Fix\n";
    echo str_repeat('-', 60) . "\n";
    
    // Connect to User Database
    $db = Database::getUserDB();
    
    // Check if alumni_board role already exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn && strpos($roleColumn['Type'], "'alumni_board'") === false) {
        // Execute SQL query to update role column
        $db->exec("
            ALTER TABLE users 
            MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
            NOT NULL DEFAULT 'member'
        ");
        echo "✅ Role alumni_board added to DB.\n\n";
    } else {
        echo "✅ Role alumni_board already exists.\n\n";
    }
    
    // ============================================
    // PART 2: Directory Fix (Permissions)
    // ============================================
    echo "Part 2: Directory Permissions Fix\n";
    echo str_repeat('-', 60) . "\n";
    
    $uploadsDir = __DIR__ . '/uploads/invoices';
    
    // Check if folder exists
    if (!is_dir($uploadsDir)) {
        // Create directory with permissions 0777
        if (!mkdir($uploadsDir, 0777, true)) {
            throw new Exception("Failed to create directory: $uploadsDir");
        }
    } else {
        // If it exists, force permissions
        if (!chmod($uploadsDir, 0777)) {
            throw new Exception("Failed to set permissions on directory: $uploadsDir");
        }
    }
    
    // Create .htaccess file to prevent directory listing
    $htaccessPath = $uploadsDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccessContent = "Options -Indexes";
        if (file_put_contents($htaccessPath, $htaccessContent) === false) {
            throw new Exception("Failed to create .htaccess file");
        }
    }
    
    echo "✅ Upload folder ready.\n";
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Setup completed successfully!\n\n";
    echo "Summary:\n";
    echo "- Database: 'alumni_board' role added to users table\n";
    echo "- Directory: uploads/invoices created with 0777 permissions\n";
    echo "- Security: .htaccess file created to prevent directory listing\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
