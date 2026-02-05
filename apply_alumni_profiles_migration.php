<?php
/**
 * Apply alumni_profiles table migration
 */

require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getContentDB();
    
    echo "Applying migration: add_alumni_profiles_table.sql\n";
    echo "================================================\n\n";
    
    // Read migration file
    $migrationFile = __DIR__ . '/sql/migrations/add_alumni_profiles_table.sql';
    
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
                // Check if error is about table/column/index already existing
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate column name') !== false || 
                    strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    echo "⚠ Skipped (already exists)\n\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    echo "================================================\n";
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
