<?php
/**
 * Migration: Add project type and user notification preferences
 * 
 * This migration adds:
 * 1. 'type' field to projects table (internal/external)
 * 2. 'notify_new_projects' field to users table (default 1)
 * 3. 'notify_new_events' field to users table (default 0)
 */

require_once __DIR__ . '/../src/Database.php';

try {
    echo "Starting migration: Add project type and notification preferences\n";
    echo "=============================================================\n\n";
    
    // Get database connections
    $contentDB = Database::getContentDB();
    $userDB = Database::getUserDB();
    
    // 1. Add 'type' field to projects table
    echo "1. Adding 'type' field to projects table...\n";
    try {
        $contentDB->exec("
            ALTER TABLE projects 
            ADD COLUMN type ENUM('internal', 'external') NOT NULL DEFAULT 'internal' 
            AFTER priority
        ");
        echo "   ✓ Added 'type' field to projects table\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   - 'type' field already exists in projects table\n\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Add 'notify_new_projects' field to users table
    echo "2. Adding 'notify_new_projects' field to users table...\n";
    try {
        $userDB->exec("
            ALTER TABLE users 
            ADD COLUMN notify_new_projects BOOLEAN NOT NULL DEFAULT TRUE 
            AFTER tfa_enabled
        ");
        echo "   ✓ Added 'notify_new_projects' field to users table\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   - 'notify_new_projects' field already exists in users table\n\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Add 'notify_new_events' field to users table
    echo "3. Adding 'notify_new_events' field to users table...\n";
    try {
        $userDB->exec("
            ALTER TABLE users 
            ADD COLUMN notify_new_events BOOLEAN NOT NULL DEFAULT FALSE 
            AFTER notify_new_projects
        ");
        echo "   ✓ Added 'notify_new_events' field to users table\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   - 'notify_new_events' field already exists in users table\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "=============================================================\n";
    echo "Migration completed successfully!\n\n";
    
    // Display summary
    echo "Summary of changes:\n";
    echo "  - projects.type: ENUM('internal', 'external') DEFAULT 'internal'\n";
    echo "  - users.notify_new_projects: BOOLEAN DEFAULT TRUE\n";
    echo "  - users.notify_new_events: BOOLEAN DEFAULT FALSE\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
