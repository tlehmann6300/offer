<?php
/**
 * Database Schema Update Script
 * 
 * This script executes ALTER TABLE commands to add missing columns and tables
 * to fix SQLSTATE[42S22] "Column not found" errors.
 * 
 * Run this script ONCE after deploying the consolidated schema files.
 * 
 * Usage: php update_database_schema.php
 */

require_once __DIR__ . '/includes/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "==============================================\n";
echo "Database Schema Update Script\n";
echo "==============================================\n\n";

// Track success/failure
$success_count = 0;
$error_count = 0;
$errors = [];

/**
 * Execute a SQL statement safely
 */
function executeSql($pdo, $sql, $description) {
    global $success_count, $error_count, $errors;
    
    try {
        echo "Executing: $description\n";
        $pdo->exec($sql);
        echo "✓ SUCCESS: $description\n\n";
        $success_count++;
        return true;
    } catch (PDOException $e) {
        // Ignore "Duplicate column" and "Table already exists" errors
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ SKIPPED: $description (already exists)\n\n";
            $success_count++;
            return true;
        }
        
        echo "✗ ERROR: $description\n";
        echo "   Message: " . $e->getMessage() . "\n\n";
        $error_count++;
        $errors[] = [
            'description' => $description,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

try {
    // ============================================
    // USER DATABASE UPDATES (dbs15253086)
    // ============================================
    echo "--- USER DATABASE UPDATES ---\n";
    
    $user_db = Database::getUserDB();
    
    // Add azure_roles column to users table
    executeSql(
        $user_db,
        "ALTER TABLE users ADD COLUMN azure_roles JSON DEFAULT NULL COMMENT 'Original Microsoft Entra ID roles from Azure AD authentication'",
        "Add azure_roles column to users table"
    );
    
    // Add deleted_at column to users table
    executeSql(
        $user_db,
        "ALTER TABLE users ADD COLUMN deleted_at DATETIME DEFAULT NULL COMMENT 'Timestamp when the user was soft deleted (NULL = active)'",
        "Add deleted_at column to users table"
    );
    
    // Add index for deleted_at
    executeSql(
        $user_db,
        "ALTER TABLE users ADD INDEX idx_deleted_at (deleted_at)",
        "Add index for deleted_at column"
    );
    
    // Add last_reminder_sent_at column to users table
    executeSql(
        $user_db,
        "ALTER TABLE users ADD COLUMN last_reminder_sent_at DATETIME DEFAULT NULL COMMENT 'Timestamp when the last profile reminder email was sent to the user'",
        "Add last_reminder_sent_at column to users table"
    );
    
    // Add index for last_reminder_sent_at
    executeSql(
        $user_db,
        "ALTER TABLE users ADD INDEX idx_last_reminder_sent_at (last_reminder_sent_at)",
        "Add index for last_reminder_sent_at column"
    );
    
    // Add show_birthday column to users table
    executeSql(
        $user_db,
        "ALTER TABLE users ADD COLUMN show_birthday BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Whether to display birthday publicly on profile' AFTER birthday",
        "Add show_birthday column to users table"
    );
    
    // ============================================
    // CONTENT DATABASE UPDATES (dbs15161271)
    // ============================================
    echo "\n--- CONTENT DATABASE UPDATES ---\n";
    
    $content_db = Database::getContentDB();
    
    // Add secondary_email column to alumni_profiles
    executeSql(
        $content_db,
        "ALTER TABLE alumni_profiles ADD COLUMN secondary_email VARCHAR(255) DEFAULT NULL COMMENT 'Optional secondary email address for profile display only' AFTER email",
        "Add secondary_email column to alumni_profiles table"
    );
    
    // Add microsoft_forms_url column to polls table
    executeSql(
        $content_db,
        "ALTER TABLE polls ADD COLUMN microsoft_forms_url TEXT DEFAULT NULL COMMENT 'Microsoft Forms embed URL or direct link for external survey integration'",
        "Add microsoft_forms_url column to polls table"
    );
    
    // Add visible_to_all column to polls table
    executeSql(
        $content_db,
        "ALTER TABLE polls ADD COLUMN visible_to_all BOOLEAN NOT NULL DEFAULT 0 COMMENT 'If true, show poll to all users regardless of roles'",
        "Add visible_to_all column to polls table"
    );
    
    // Add is_internal column to polls table
    executeSql(
        $content_db,
        "ALTER TABLE polls ADD COLUMN is_internal BOOLEAN NOT NULL DEFAULT 1 COMMENT 'If true, hide poll after user votes. If false (external Forms), show hide button'",
        "Add is_internal column to polls table"
    );
    
    // Add allowed_roles column to polls table
    executeSql(
        $content_db,
        "ALTER TABLE polls ADD COLUMN allowed_roles JSON DEFAULT NULL COMMENT 'JSON array of Entra roles that can see this poll (filters against user azure_roles)'",
        "Add allowed_roles column to polls table"
    );
    
    // Create poll_hidden_by_user table
    $create_poll_hidden_table = "
    CREATE TABLE IF NOT EXISTS poll_hidden_by_user (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        poll_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        hidden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        UNIQUE KEY unique_poll_user (poll_id, user_id),
        INDEX idx_poll_id (poll_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
      COMMENT='Tracks which users have manually hidden which polls'
    ";
    
    executeSql(
        $content_db,
        $create_poll_hidden_table,
        "Create poll_hidden_by_user table"
    );
    
    // Add sellers_data column to event_documentation table
    executeSql(
        $content_db,
        "ALTER TABLE event_documentation ADD COLUMN sellers_data JSON DEFAULT NULL COMMENT 'JSON array of seller entries with name, items, quantity, and revenue'",
        "Add sellers_data column to event_documentation table"
    );
    
    // Create event_financial_stats table
    $create_financial_stats_table = "
    CREATE TABLE IF NOT EXISTS event_financial_stats (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id INT UNSIGNED NOT NULL,
        category ENUM('Verkauf', 'Kalkulation') NOT NULL COMMENT 'Category: Sales or Calculation',
        item_name VARCHAR(255) NOT NULL COMMENT 'Item name, e.g., Brezeln, Äpfel, Grillstand',
        quantity INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Quantity sold or calculated',
        revenue DECIMAL(10, 2) DEFAULT NULL COMMENT 'Revenue in EUR (optional for calculations)',
        record_year YEAR NOT NULL COMMENT 'Year of record for historical comparison',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by INT UNSIGNED NOT NULL COMMENT 'User who created the record',
        
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        
        INDEX idx_event_id (event_id),
        INDEX idx_category (category),
        INDEX idx_record_year (record_year),
        INDEX idx_event_year (event_id, record_year),
        INDEX idx_created_by (created_by)
    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_unicode_ci
      COMMENT='Financial statistics for events - tracks sales and calculations with yearly comparison'
    ";
    
    executeSql(
        $content_db,
        $create_financial_stats_table,
        "Create event_financial_stats table"
    );
    
    // ============================================
    // SUMMARY
    // ============================================
    echo "==============================================\n";
    echo "SUMMARY\n";
    echo "==============================================\n";
    echo "Successful operations: $success_count\n";
    echo "Failed operations: $error_count\n";
    
    if ($error_count > 0) {
        echo "\n--- ERRORS ---\n";
        foreach ($errors as $error) {
            echo "- {$error['description']}: {$error['error']}\n";
        }
        echo "\n";
        exit(1);
    } else {
        echo "\n✓ All schema updates completed successfully!\n";
        echo "The database schema is now up to date.\n";
        exit(0);
    }
    
} catch (Exception $e) {
    echo "\n✗ FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
