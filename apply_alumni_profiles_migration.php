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
    
    // Execute the migration SQL
    echo "Executing: Creating alumni_profiles table...\n";
    try {
        $db->exec($sql);
        echo "✓ Success\n\n";
    } catch (PDOException $e) {
        // Check if error is about table already existing
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ Skipped (table already exists)\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "================================================\n";
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
