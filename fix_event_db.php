<?php
/**
 * Hotfix script for event database
 * Checks and adds missing columns to events table
 * Self-deletes after execution
 */

require_once __DIR__ . '/includes/database.php';

try {
    // Connect to Content database
    $db = Database::getContentDB();
    
    echo "Verbindung zur Datenbank hergestellt.\n";
    
    // Check if maps_link column exists
    $stmt = $db->query("SHOW COLUMNS FROM events LIKE 'maps_link'");
    $mapsLinkExists = $stmt->rowCount() > 0;
    
    if (!$mapsLinkExists) {
        echo "Spalte 'maps_link' fehlt, wird hinzugefügt...\n";
        $db->exec("ALTER TABLE events ADD COLUMN maps_link VARCHAR(255) DEFAULT NULL");
        echo "Spalte 'maps_link' erfolgreich hinzugefügt.\n";
    } else {
        echo "Spalte 'maps_link' ist bereits vorhanden.\n";
    }
    
    // Check if image_path column exists
    $stmt = $db->query("SHOW COLUMNS FROM events LIKE 'image_path'");
    $imagePathExists = $stmt->rowCount() > 0;
    
    if (!$imagePathExists) {
        echo "Spalte 'image_path' fehlt, wird hinzugefügt...\n";
        $db->exec("ALTER TABLE events ADD COLUMN image_path VARCHAR(255) DEFAULT NULL");
        echo "Spalte 'image_path' erfolgreich hinzugefügt.\n";
    } else {
        echo "Spalte 'image_path' ist bereits vorhanden.\n";
    }
    
    echo "\n✓ Datenbank repariert.\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    exit(1);
}

// Self-delete this file
unlink(__FILE__);
