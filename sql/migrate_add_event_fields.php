<?php
/**
 * Migration Script - Add maps_link and registration dates to events table
 * Run this script to update the database schema for existing installations
 * 
 * Usage: php sql/migrate_add_event_fields.php
 */

require_once __DIR__ . '/../includes/database.php';

echo "=== Event Table Migration Script ===\n\n";

try {
    $db = Database::getContentDB();
    
    echo "Connecting to database...\n";
    
    // Check if columns already exist
    $stmt = $db->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events'
    ");
    $stmt->execute();
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
    
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n\n";
    
    // Add maps_link column if it doesn't exist
    if (!in_array('maps_link', $existingColumns)) {
        echo "Adding 'maps_link' column...\n";
        $db->exec("ALTER TABLE events ADD COLUMN maps_link VARCHAR(255) DEFAULT NULL AFTER location");
        echo "✓ 'maps_link' column added successfully\n\n";
    } else {
        echo "✓ 'maps_link' column already exists\n\n";
    }
    
    // Add registration_start column if it doesn't exist
    if (!in_array('registration_start', $existingColumns)) {
        echo "Adding 'registration_start' column...\n";
        $db->exec("ALTER TABLE events ADD COLUMN registration_start DATETIME DEFAULT NULL AFTER end_time");
        echo "✓ 'registration_start' column added successfully\n\n";
    } else {
        echo "✓ 'registration_start' column already exists\n\n";
    }
    
    // Add registration_end column if it doesn't exist
    if (!in_array('registration_end', $existingColumns)) {
        echo "Adding 'registration_end' column...\n";
        $db->exec("ALTER TABLE events ADD COLUMN registration_end DATETIME DEFAULT NULL AFTER registration_start");
        echo "✓ 'registration_end' column added successfully\n\n";
    } else {
        echo "✓ 'registration_end' column already exists\n\n";
    }
    
    // Check and update location column length if needed
    $stmt = $db->prepare("
        SELECT CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events'
        AND COLUMN_NAME = 'location'
    ");
    $stmt->execute();
    $locationLength = $stmt->fetchColumn();
    
    if ($locationLength && $locationLength < 255) {
        echo "Updating 'location' column length from {$locationLength} to 255...\n";
        $db->exec("ALTER TABLE events MODIFY COLUMN location VARCHAR(255) DEFAULT NULL");
        echo "✓ 'location' column length updated successfully\n\n";
    } else {
        echo "✓ 'location' column length is already 255\n\n";
    }
    
    echo "=== Migration Completed Successfully ===\n";
    echo "\nNext steps:\n";
    echo "1. The automatic status calculation is now active for all new and updated events\n";
    echo "2. Existing events will have their status recalculated when they are viewed or updated\n";
    echo "3. You can optionally set registration_start and registration_end for existing events\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    return;
}
