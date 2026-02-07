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
        echo "✅ Role alumni_board added.\n\n";
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
    
    // Create .gitkeep file to ensure Git tracking
    $gitkeepPath = $uploadsDir . '/.gitkeep';
    if (!file_exists($gitkeepPath)) {
        if (file_put_contents($gitkeepPath, '') === false) {
            throw new Exception("Failed to create .gitkeep file");
        }
    }
    
    echo "✅ Upload folder created and writable.\n\n";
    
    // ============================================
    // PART 3: Security
    // ============================================
    echo "Part 3: Security Protection\n";
    echo str_repeat('-', 60) . "\n";
    
    // Add index.php to prevent directory listing
    $indexPath = $uploadsDir . '/index.php';
    if (!file_exists($indexPath)) {
        $indexContent = "<?php\n// Silence - prevents directory listing\n";
        if (file_put_contents($indexPath, $indexContent) === false) {
            throw new Exception("Failed to create index.php security file");
        }
        echo "✅ Security file (index.php) created to prevent directory listing.\n";
    } else {
        echo "✅ Security file already exists.\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Setup completed successfully!\n\n";
    echo "Summary:\n";
    echo "- Database: 'alumni_board' role added to users table\n";
    echo "- Directory: uploads/invoices created with 0777 permissions\n";
    echo "- Security: Directory listing protection enabled\n";
    
} catch (PDOException $e) {
    echo "\n❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
