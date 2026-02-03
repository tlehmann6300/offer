<?php
/**
 * Apply inventory import fields migration
 */

require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getContentDB();
    
    echo "Applying migration: add_inventory_import_fields.sql\n";
    echo "================================================\n\n";
    
    // Read migration file
    $migrationFile = __DIR__ . '/sql/migrations/add_inventory_import_fields.sql';
    
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
                // Check if error is about column already existing or index already existing
                if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                    strpos($e->getMessage(), 'Duplicate key name') !== false) {
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
    $stmt = $db->query("DESCRIBE inventory");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['serial_number', 'status', 'purchase_date'];
    $foundColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $foundColumns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ Column '$col' NOT FOUND\n";
        }
    }
    
    echo "\nMigration verification complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
