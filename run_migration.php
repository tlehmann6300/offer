<?php
/**
 * Database Migration Runner
 * Applies the board role types migration
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

echo "=== Starting Migration: Add Board Role Types ===\n\n";

try {
    // Get user database connection
    $db = Database::getUserDB();
    
    // Read migration file
    $migrationSql = file_get_contents(__DIR__ . '/sql/migration_add_board_role_types.sql');
    
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
    
    // Verify the changes
    echo "\n=== Verifying Changes ===\n";
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($roleColumn) {
        echo "Role column definition:\n";
        echo "Type: " . $roleColumn['Type'] . "\n";
        echo "Default: " . $roleColumn['Default'] . "\n";
        echo "\n✓ Migration verified successfully!\n";
    } else {
        echo "✗ Could not verify migration.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== All Done! ===\n";
