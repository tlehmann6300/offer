<?php
/**
 * Apply EasyVerein integration migration
 */

require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getContentDB();
    
    echo "Applying migration: adapt_inventory_for_easyverein.sql\n";
    echo "================================================\n\n";
    
    // Read migration file
    $migrationFile = __DIR__ . '/sql/migrations/adapt_inventory_for_easyverein.sql';
    
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
    
    $requiredColumns = ['easyverein_id', 'last_synced_at', 'is_archived_in_easyverein'];
    $foundColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $foundColumns)) {
            echo "✓ Column '$col' exists\n";
        } else {
            echo "✗ Column '$col' NOT FOUND\n";
        }
    }
    
    // Verify unique index on easyverein_id
    echo "\nVerifying indexes...\n";
    $stmt = $db->query("SHOW INDEX FROM inventory WHERE Key_name = 'idx_easyverein_id'");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($indexes) > 0) {
        echo "✓ Unique index 'idx_easyverein_id' exists\n";
    } else {
        echo "✗ Unique index 'idx_easyverein_id' NOT FOUND\n";
    }
    
    echo "\nMigration verification complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
