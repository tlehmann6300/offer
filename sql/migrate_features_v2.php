<?php
/**
 * Migration Script: Add Features v2 - Security, Notifications, and Project Types
 * 
 * This migration adds:
 * 1. Security columns to users table (failed_login_attempts, locked_until, is_locked_permanently)
 * 2. Notification preference columns to users table (notify_new_projects, notify_new_events)
 * 3. Project type column to projects table (type: internal/external)
 */

require_once __DIR__ . '/../includes/database.php';

try {
    echo "Starting migration: Add Features v2 - Security, Notifications, and Project Types\n";
    echo str_repeat('=', 70) . "\n\n";
    
    // ========================================
    // PART 1: Add Security Columns to Users Table
    // ========================================
    
    echo "PART 1: Adding security columns to users table\n";
    echo str_repeat('-', 70) . "\n";
    
    $userDB = Database::getUserDB();
    
    // Get current columns
    $stmt = $userDB->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Check and add failed_login_attempts column
    if (!in_array('failed_login_attempts', $columnNames)) {
        echo "Adding failed_login_attempts column...\n";
        $userDB->exec("ALTER TABLE users ADD COLUMN failed_login_attempts INT NOT NULL DEFAULT 0");
        echo "✓ Successfully added failed_login_attempts column\n";
    } else {
        echo "✓ failed_login_attempts column already exists\n";
    }
    
    // Check and add locked_until column
    if (!in_array('locked_until', $columnNames)) {
        echo "Adding locked_until column...\n";
        $userDB->exec("ALTER TABLE users ADD COLUMN locked_until DATETIME DEFAULT NULL");
        echo "✓ Successfully added locked_until column\n";
    } else {
        echo "✓ locked_until column already exists\n";
    }
    
    // Check and add is_locked_permanently column
    if (!in_array('is_locked_permanently', $columnNames)) {
        echo "Adding is_locked_permanently column...\n";
        $userDB->exec("ALTER TABLE users ADD COLUMN is_locked_permanently BOOLEAN NOT NULL DEFAULT 0");
        echo "✓ Successfully added is_locked_permanently column\n";
    } else {
        echo "✓ is_locked_permanently column already exists\n";
    }
    
    echo "\n";
    
    // ========================================
    // PART 2: Add Notification Preference Columns to Users Table
    // ========================================
    
    echo "PART 2: Adding notification preference columns to users table\n";
    echo str_repeat('-', 70) . "\n";
    
    // Refresh columns list after previous changes
    $stmt = $userDB->query("SHOW COLUMNS FROM users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Check and add notify_new_projects column (default 1 = YES)
    if (!in_array('notify_new_projects', $columnNames)) {
        echo "Adding notify_new_projects column (default: YES)...\n";
        $userDB->exec("ALTER TABLE users ADD COLUMN notify_new_projects BOOLEAN NOT NULL DEFAULT 1");
        echo "✓ Successfully added notify_new_projects column\n";
    } else {
        echo "✓ notify_new_projects column already exists\n";
    }
    
    // Check and add notify_new_events column (default 0 = NO)
    if (!in_array('notify_new_events', $columnNames)) {
        echo "Adding notify_new_events column (default: NO)...\n";
        $userDB->exec("ALTER TABLE users ADD COLUMN notify_new_events BOOLEAN NOT NULL DEFAULT 0");
        echo "✓ Successfully added notify_new_events column\n";
    } else {
        echo "✓ notify_new_events column already exists\n";
    }
    
    echo "\n";
    
    // ========================================
    // PART 3: Add Type Column to Projects Table
    // ========================================
    
    echo "PART 3: Adding type column to projects table\n";
    echo str_repeat('-', 70) . "\n";
    
    $contentDB = Database::getContentDB();
    
    // Check if projects table exists
    $stmt = $contentDB->query("SHOW TABLES LIKE 'projects'");
    $projectsExists = $stmt->fetch() !== false;
    
    if ($projectsExists) {
        // Get current columns
        $stmt = $contentDB->query("SHOW COLUMNS FROM projects");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'Field');
        
        // Check and add type column
        if (!in_array('type', $columnNames)) {
            echo "Adding type column to projects table (ENUM: internal/external)...\n";
            $contentDB->exec("ALTER TABLE projects ADD COLUMN type ENUM('internal', 'external') NOT NULL DEFAULT 'internal'");
            echo "✓ Successfully added type column\n";
        } else {
            echo "✓ type column already exists\n";
        }
    } else {
        echo "⚠ Warning: projects table not found (skipping)\n";
    }
    
    echo "\n";
    echo str_repeat('=', 70) . "\n";
    echo "✓ Migration completed successfully!\n";
    echo "\n";
    echo "Summary:\n";
    echo "  Users Table (Security Features):\n";
    echo "    - failed_login_attempts (INT, default 0)\n";
    echo "    - locked_until (DATETIME, nullable)\n";
    echo "    - is_locked_permanently (BOOLEAN, default 0)\n";
    echo "  Users Table (Notification Preferences):\n";
    echo "    - notify_new_projects (BOOLEAN, default 1 = YES)\n";
    echo "    - notify_new_events (BOOLEAN, default 0 = NO)\n";
    if ($projectsExists) {
        echo "  Projects Table:\n";
        echo "    - type (ENUM: 'internal', 'external', default 'internal')\n";
    }
    
} catch (Exception $e) {
    echo "\n";
    echo str_repeat('=', 70) . "\n";
    echo "❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
