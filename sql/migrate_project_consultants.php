<?php
/**
 * Migration Script - Add max_consultants and documentation to projects table
 * Run this script to update the database schema for existing installations
 * 
 * Usage: php sql/migrate_project_consultants.php
 */

require_once __DIR__ . '/../includes/database.php';

echo "=== Project Consultants Migration Script ===\n\n";

try {
    $db = Database::getContentDB();
    
    echo "Connecting to database...\n";
    
    // Check if columns already exist
    $stmt = $db->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'projects'
    ");
    $stmt->execute();
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
    
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n\n";
    
    // Add max_consultants column if it doesn't exist
    if (!in_array('max_consultants', $existingColumns)) {
        echo "Adding 'max_consultants' column...\n";
        $db->exec("ALTER TABLE projects ADD COLUMN max_consultants INT NOT NULL DEFAULT 1 AFTER status");
        echo "✓ 'max_consultants' column added successfully\n\n";
    } else {
        echo "✓ 'max_consultants' column already exists\n\n";
    }
    
    // Add documentation column if it doesn't exist
    if (!in_array('documentation', $existingColumns)) {
        echo "Adding 'documentation' column...\n";
        $db->exec("ALTER TABLE projects ADD COLUMN documentation TEXT DEFAULT NULL AFTER end_date");
        echo "✓ 'documentation' column added successfully\n\n";
    } else {
        echo "✓ 'documentation' column already exists\n\n";
    }
    
    echo "Migration erfolgreich\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
