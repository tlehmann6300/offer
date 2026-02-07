<?php
/**
 * Invoice Module Readiness Check
 * 
 * Checks if the database is ready for the Invoice Module by verifying
 * that the users table has the 'alumni_board' enum value in the role column.
 */

require_once __DIR__ . '/includes/database.php';

// Establish database connection
try {
    $pdo = Database::getUserDB();
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Query to check the ENUM values for the role column in the users table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        echo "❌ Error: Could not find 'role' column in users table.\n";
        exit(1);
    }
    
    // Parse ENUM values from the Type field
    $type = $row['Type'];
    preg_match("/^enum\((.+)\)$/", $type, $matches);
    
    if (!isset($matches[1])) {
        echo "❌ Error: 'role' column is not an ENUM type.\n";
        exit(1);
    }
    
    // Extract enum values
    $enumValues = array_map(function($value) {
        return trim($value, "'");
    }, explode(',', $matches[1]));
    
    // Check if 'alumni_board' exists in the enum values
    if (in_array('alumni_board', $enumValues)) {
        echo "✅ Ready for Expenses\n";
        exit(0);
    } else {
        echo "⚠️ Warning: Run setup_invoice_requirements.php first!\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ Database query failed: " . $e->getMessage() . "\n";
    exit(1);
}
