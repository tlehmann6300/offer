<?php
/**
 * Migration: Add Microsoft Entra ID Profile Fields
 * 
 * Adds optional fields to users table:
 * - job_title (VARCHAR) - User's position/job title from Entra
 * - company (VARCHAR) - User's company name from Entra
 * - entra_roles (TEXT) - Comma-separated Entra role names for display
 * 
 * Also verifies is_profile_complete exists with proper default.
 */

require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::getUserDB();
    
    echo "Starting migration: Add Microsoft Entra ID Profile Fields\n";
    echo "=========================================================\n\n";
    
    // Check if job_title column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'job_title'");
    if ($stmt->rowCount() === 0) {
        echo "Adding job_title column...\n";
        $db->exec("ALTER TABLE users ADD COLUMN job_title VARCHAR(255) DEFAULT NULL COMMENT 'Job title from Microsoft Entra ID'");
        echo "✓ job_title column added successfully\n";
    } else {
        echo "✓ job_title column already exists\n";
    }
    
    // Check if company column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'company'");
    if ($stmt->rowCount() === 0) {
        echo "Adding company column...\n";
        $db->exec("ALTER TABLE users ADD COLUMN company VARCHAR(255) DEFAULT NULL COMMENT 'Company name from Microsoft Entra ID'");
        echo "✓ company column added successfully\n";
    } else {
        echo "✓ company column already exists\n";
    }
    
    // Check if entra_roles column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'entra_roles'");
    if ($stmt->rowCount() === 0) {
        echo "Adding entra_roles column...\n";
        $db->exec("ALTER TABLE users ADD COLUMN entra_roles TEXT DEFAULT NULL COMMENT 'Comma-separated Microsoft Entra role names for display'");
        echo "✓ entra_roles column added successfully\n";
    } else {
        echo "✓ entra_roles column already exists\n";
    }
    
    // Verify is_profile_complete column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_profile_complete'");
    if ($stmt->rowCount() === 0) {
        // Note: The column name in the schema is 'profile_complete', not 'is_profile_complete'
        // Check for the correct column name
        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'profile_complete'");
        if ($stmt->rowCount() > 0) {
            echo "✓ profile_complete column already exists\n";
        } else {
            echo "Adding profile_complete column...\n";
            $db->exec("ALTER TABLE users ADD COLUMN profile_complete BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Flag to track if user has completed initial profile setup'");
            echo "✓ profile_complete column added successfully\n";
        }
    } else {
        echo "✓ is_profile_complete column already exists\n";
    }
    
    echo "\n=========================================================\n";
    echo "Migration completed successfully!\n";
    echo "=========================================================\n";
    
} catch (Exception $e) {
    echo "ERROR: Migration failed\n";
    echo "Message: " . $e->getMessage() . "\n";
    exit(1);
}
