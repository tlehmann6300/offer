<?php
/**
 * Project Module Migration Script
 * Migrates the projects module tables to the Content database
 */

require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getContentDB();
    
    // Check if any of the project module tables already exist
    $tablesToCheck = ['projects', 'project_applications', 'project_assignments'];
    $existingTables = [];
    
    foreach ($tablesToCheck as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch() !== false) {
            $existingTables[] = $table;
        }
    }
    
    if (!empty($existingTables)) {
        echo "Tabellen existieren bereits: " . implode(', ', $existingTables) . "\n";
        echo "Migration wird übersprungen.\n";
        echo "Erfolgreich migriert\n";
        return;
    }
    
    // Read migration file
    $migrationFile = __DIR__ . '/sql/migrations/add_project_module.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration-Datei nicht gefunden: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split SQL into individual CREATE TABLE statements
    // Each statement ends with ); followed by comments or next CREATE TABLE
    $pattern = '/(CREATE TABLE.*?);/is';
    preg_match_all($pattern, $sql, $matches);
    
    if (empty($matches[1])) {
        throw new Exception("Keine gültigen SQL-Statements gefunden");
    }
    
    $statements = $matches[1];
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                throw new Exception("Fehler beim Ausführen der Migration: " . $e->getMessage());
            }
        }
    }
    
    echo "Erfolgreich migriert\n";
    
} catch (Exception $e) {
    echo "Fehler: " . $e->getMessage() . "\n";
    return;
}
