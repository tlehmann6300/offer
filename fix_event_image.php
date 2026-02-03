<?php
/**
 * Database Migration: Add image_path column to events table
 * Description: Adds the image_path column to the events table for storing event images
 * Run: php fix_event_image.php
 */

require_once __DIR__ . '/includes/database.php';

try {
    // Get Content Database connection
    $db = Database::getContentDB();
    
    echo "=== Event Image Path Migration ===\n\n";
    
    // Check if column already exists
    $stmt = $db->prepare("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events'
    ");
    $stmt->execute();
    $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
    
    // Execute ALTER TABLE only if column doesn't exist
    if (!in_array('image_path', $existingColumns)) {
        $db->exec("ALTER TABLE events ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
        echo "✓ Spalte image_path erfolgreich angelegt\n";
    } else {
        echo "ℹ Spalte image_path existiert bereits\n";
    }
    
    echo "\n=== Migration abgeschlossen ===\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration fehlgeschlagen: " . $e->getMessage() . "\n";
    error_log("Event image path migration failed: " . $e->getMessage());
    exit(1);
}
