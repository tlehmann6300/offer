<?php
/**
 * Migration Script: Add event_financial_stats table
 * Run this script once to create the new table
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';

echo "Starting migration: event_financial_stats table\n";

try {
    $db = Database::getContentDB();
    
    // Check if table already exists
    $stmt = $db->query("SHOW TABLES LIKE 'event_financial_stats'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✓ Table 'event_financial_stats' already exists. Skipping migration.\n";
    } else {
        // Create the table directly
        $sql = "CREATE TABLE IF NOT EXISTS event_financial_stats (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_id INT UNSIGNED NOT NULL,
            category ENUM('Verkauf', 'Kalkulation') NOT NULL COMMENT 'Category: Sales or Calculation',
            item_name VARCHAR(255) NOT NULL COMMENT 'Item name, e.g., Brezeln, Äpfel, Grillstand',
            quantity INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantity sold or calculated',
            revenue DECIMAL(10, 2) DEFAULT NULL COMMENT 'Revenue in EUR (optional for calculations)',
            record_year YEAR NOT NULL COMMENT 'Year of record for historical comparison',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT UNSIGNED NOT NULL COMMENT 'User who created the record',
            
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            
            INDEX idx_event_id (event_id),
            INDEX idx_category (category),
            INDEX idx_record_year (record_year),
            INDEX idx_event_year (event_id, record_year),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB
          DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci
          COMMENT='Historical financial statistics for events'";
        
        $db->exec($sql);
        echo "✓ Migration successful: event_financial_stats table created\n";
    }
    
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
