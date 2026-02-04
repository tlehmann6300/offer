<?php
/**
 * Migration Script - Add file_path to projects table and ensure status ENUM contains 'draft' and 'open'
 * Run this script to update the database schema for existing installations
 * 
 * Usage: php sql/migrate_project_files.php
 */

require_once __DIR__ . '/../includes/database.php';

echo "=== Project Files Migration Script ===\n\n";

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
    
    // Add file_path column if it doesn't exist
    if (!in_array('file_path', $existingColumns)) {
        echo "Adding 'file_path' column...\n";
        $db->exec("ALTER TABLE projects ADD COLUMN file_path VARCHAR(255) DEFAULT NULL AFTER image_path");
        echo "✓ 'file_path' column added successfully\n\n";
    } else {
        echo "✓ 'file_path' column already exists\n\n";
    }
    
    // Check if status column is an ENUM and ensure it contains 'draft' and 'open'
    $stmt = $db->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'projects'
        AND COLUMN_NAME = 'status'
    ");
    $stmt->execute();
    $columnType = $stmt->fetchColumn();
    
    if ($columnType && strpos($columnType, 'enum') === 0) {
        echo "Status column is an ENUM: {$columnType}\n";
        
        // Parse existing ENUM values
        preg_match_all("/'([^']+)'/", $columnType, $matches);
        $existingValues = $matches[1];
        
        echo "Current ENUM values: " . implode(', ', $existingValues) . "\n";
        
        // Check if 'draft' and 'open' are present
        $hasDraft = in_array('draft', $existingValues);
        $hasOpen = in_array('open', $existingValues);
        
        if (!$hasDraft || !$hasOpen) {
            $newValues = $existingValues;
            
            if (!$hasDraft) {
                echo "Adding 'draft' to status ENUM...\n";
                $newValues[] = 'draft';
            }
            
            if (!$hasOpen) {
                echo "Adding 'open' to status ENUM...\n";
                $newValues[] = 'open';
            }
            
            // Rebuild ENUM with all values (use PDO quote for proper escaping)
            $escapedValues = array_map(function($value) use ($db) {
                $quoted = $db->quote($value);
                // Remove the outer quotes added by PDO::quote
                return substr($quoted, 1, -1);
            }, $newValues);
            $enumValues = "'" . implode("','", $escapedValues) . "'";
            $db->exec("ALTER TABLE projects MODIFY COLUMN status ENUM({$enumValues}) NOT NULL DEFAULT 'draft'");
            echo "✓ Status ENUM updated successfully\n\n";
        } else {
            echo "✓ Status ENUM already contains 'draft' and 'open'\n\n";
        }
    } else {
        echo "⚠ Status column is not an ENUM or does not exist\n\n";
    }
    
    echo "Migration für Projekt-Dateien erfolgreich\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
