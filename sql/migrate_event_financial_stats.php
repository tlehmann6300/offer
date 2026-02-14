<?php
/**
 * Migration Script: Add event_financial_stats table
 * Run this script once to create the new table
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Database.php';

echo "Starting migration: event_financial_stats table\n";

try {
    $db = Database::getContentDB();
    
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/add_event_financial_stats_table.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read SQL file");
    }
    
    // Execute the SQL
    $db->exec($sql);
    
    echo "✓ Migration successful: event_financial_stats table created\n";
    
    // Verify the table was created
    $stmt = $db->query("SHOW TABLES LIKE 'event_financial_stats'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✓ Table verification successful\n";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE event_financial_stats");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTable structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        throw new Exception("Table verification failed");
    }
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration completed successfully!\n";
