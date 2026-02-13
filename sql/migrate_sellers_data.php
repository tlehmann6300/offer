<?php
/**
 * Database Migration Script
 * Apply sellers_data column to event_documentation table
 */

require_once __DIR__ . '/../src/Database.php';

try {
    $db = Database::getContentDB();
    
    echo "Starting database migration...\n";
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM event_documentation LIKE 'sellers_data'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "Column 'sellers_data' already exists. Skipping migration.\n";
    } else {
        // Add the column
        $sql = "ALTER TABLE event_documentation 
                ADD COLUMN sellers_data JSON DEFAULT NULL 
                COMMENT 'JSON array of seller entries with name, items, quantity, and revenue'";
        
        $db->exec($sql);
        echo "Successfully added 'sellers_data' column to event_documentation table.\n";
    }
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
