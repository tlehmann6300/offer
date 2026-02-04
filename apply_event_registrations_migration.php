<?php
/**
 * Apply event_registrations table migration
 */

require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getContentDB();
    
    echo "Applying migration: add_event_registrations_table.sql\n";
    echo "================================================\n\n";
    
    // Read migration file
    $migrationFile = __DIR__ . '/sql/migrations/add_event_registrations_table.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            // Filter out empty statements and comments
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 100) . "...\n";
            try {
                $db->exec($statement);
                echo "✓ Success\n\n";
            } catch (PDOException $e) {
                // Check if error is about table already existing
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo "⚠ Skipped (already exists)\n\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    echo "================================================\n";
    echo "Migration completed successfully!\n\n";
    
    // Verify the changes
    echo "Verifying table structure...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'event_registrations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✓ Table 'event_registrations' exists\n";
        
        // Check columns
        $stmt = $db->query("DESCRIBE event_registrations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $foundColumns = array_column($columns, 'Field');
        
        $requiredColumns = ['id', 'event_id', 'user_id', 'registered_at', 'status'];
        foreach ($requiredColumns as $col) {
            if (in_array($col, $foundColumns)) {
                echo "✓ Column '$col' exists\n";
            } else {
                echo "✗ Column '$col' NOT FOUND\n";
            }
        }
    } else {
        echo "✗ Table 'event_registrations' NOT FOUND\n";
    }
    
    echo "\nMigration verification complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
