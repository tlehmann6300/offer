<?php
/**
 * Database Migration Runner for Event Documentation
 * Creates the event_documentation table
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database.php';

echo "=== Starting Migration: Add Event Documentation Table ===\n\n";

try {
    // Get content database connection
    $db = Database::getContentDB();
    
    // Read migration file
    $migrationSql = file_get_contents(__DIR__ . '/sql/migration_event_documentation.sql');
    
    // Remove USE statement and comments, then split by semicolons
    $migrationSql = preg_replace('/USE\s+\w+;/i', '', $migrationSql);
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
        if (empty($statement) || strpos(trim($statement), '--') === 0 || strpos(trim($statement), '/*') === 0) {
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
    
    // Verify the changes
    echo "\n=== Verifying Changes ===\n";
    $stmt = $db->query("SHOW TABLES LIKE 'event_documentation'");
    $table = $stmt->fetch();
    
    if ($table) {
        echo "✓ event_documentation table created successfully!\n";
        
        // Show table structure
        $stmt = $db->query("DESCRIBE event_documentation");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nTable structure:\n";
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "✗ Could not verify table creation.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== All Done! ===\n";
