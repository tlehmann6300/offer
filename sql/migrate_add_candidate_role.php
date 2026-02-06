<?php
/**
 * Migration Script: Add Candidate Role to Users Table
 * 
 * This migration modifies the users table role ENUM to include 'candidate'.
 * The role ENUM will be updated to: 'admin', 'board', 'head', 'member', 'alumni', 'candidate'
 * Default role is set to 'member'.
 */

require_once __DIR__ . '/../includes/database.php';

try {
    echo "Starting migration: Add Candidate Role\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $userDB = Database::getUserDB();
    
    // Check if 'candidate' role already exists in users table
    $stmt = $userDB->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn && strpos($roleColumn['Type'], "'candidate'") === false) {
        echo "Adding 'candidate' role to users table...\n";
        $userDB->exec("
            ALTER TABLE users 
            MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
            NOT NULL DEFAULT 'member'
        ");
        echo "✅ Rolle Anwärter (candidate) erfolgreich zur Datenbank hinzugefügt\n";
    } else {
        echo "✅ Rolle Anwärter (candidate) erfolgreich zur Datenbank hinzugefügt\n";
        echo "(Role 'candidate' already exists in users table)\n";
    }
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "Migration completed successfully!\n";
    
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
