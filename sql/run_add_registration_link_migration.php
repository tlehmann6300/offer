<?php
/**
 * Migration script to add registration_link column to events table
 */

require_once __DIR__ . '/../includes/database.php';

try {
    $db = Database::getContentDB();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM events LIKE 'registration_link'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "Column 'registration_link' already exists in events table.\n";
        exit(0);
    }
    
    // Add the column
    $sql = "ALTER TABLE events 
            ADD COLUMN registration_link TEXT DEFAULT NULL 
            COMMENT 'External registration link (e.g., Microsoft Forms URL) for event registration' 
            AFTER external_link";
    
    $db->exec($sql);
    
    echo "Successfully added 'registration_link' column to events table.\n";
    exit(0);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
