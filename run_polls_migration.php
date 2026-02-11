<?php
/**
 * Database Migration Runner - Polls System
 * Applies the polls, poll_options, and poll_votes tables migration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

echo "=== Starting Migration: Polls System ===\n\n";

try {
    // Get content database connection
    $db = Database::getContentDB();
    
    // Read migration file
    $migrationSql = file_get_contents(__DIR__ . '/sql/migration_polls.sql');
    
    // Remove comments and split by semicolons
    $statements = array_filter(
        array_map('trim', 
            preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $migrationSql)
        )
    );
    
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    // Execute each statement
    $successCount = 0;
    foreach ($statements as $index => $statement) {
        // Skip empty statements and comments
        if (empty($statement) || strpos(trim($statement), '--') === 0) {
            continue;
        }
        
        echo "Executing statement " . ($index + 1) . "...\n";
        echo substr($statement, 0, 100) . "...\n";
        
        try {
            $db->exec($statement);
            echo "✓ Success\n\n";
            $successCount++;
        } catch (PDOException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n\n";
            // Continue with other statements even if one fails
        }
    }
    
    echo "=== Migration Complete ===\n";
    echo "Successfully executed $successCount statements.\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
