<?php
/**
 * Test Script for Candidate Role and Inventory Migration
 * Validates that the migration was successful
 */

require_once __DIR__ . '/../includes/database.php';

class MigrationTest {
    private $errors = [];
    private $warnings = [];
    private $successes = [];
    
    public function run(): bool {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "Testing: Candidate Role and Inventory Migration\n";
        echo str_repeat('=', 70) . "\n\n";
        
        // Test user database changes
        $this->testUserDatabaseChanges();
        
        // Test content database changes
        $this->testContentDatabaseChanges();
        
        // Display results
        $this->displayResults();
        
        return empty($this->errors);
    }
    
    private function testUserDatabaseChanges(): void {
        echo "Testing User Database Changes...\n";
        echo str_repeat('-', 70) . "\n";
        
        try {
            $db = Database::getUserDB();
            
            // Test 1: Check users table role enum
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
            $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$roleColumn) {
                $this->errors[] = "Users table 'role' column not found";
            } else {
                if (strpos($roleColumn['Type'], 'candidate') !== false) {
                    $this->successes[] = "✓ Users table has 'candidate' role in ENUM";
                } else {
                    $this->errors[] = "Users table 'role' ENUM missing 'candidate' value";
                    $this->warnings[] = "Current ENUM: " . $roleColumn['Type'];
                }
            }
            
            // Test 2: Check invitation_tokens table role enum
            $stmt = $db->query("SHOW COLUMNS FROM invitation_tokens LIKE 'role'");
            $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$roleColumn) {
                $this->errors[] = "invitation_tokens table 'role' column not found";
            } else {
                if (strpos($roleColumn['Type'], 'candidate') !== false) {
                    $this->successes[] = "✓ invitation_tokens table has 'candidate' role in ENUM";
                } else {
                    $this->errors[] = "invitation_tokens table 'role' ENUM missing 'candidate' value";
                    $this->warnings[] = "Current ENUM: " . $roleColumn['Type'];
                }
            }
            
        } catch (Exception $e) {
            $this->errors[] = "User database error: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function testContentDatabaseChanges(): void {
        echo "Testing Content Database Changes...\n";
        echo str_repeat('-', 70) . "\n";
        
        try {
            $db = Database::getContentDB();
            
            // Test 1: Check if inventory table exists
            $stmt = $db->query("SHOW TABLES LIKE 'inventory'");
            $tableExists = $stmt->fetch() !== false;
            
            if (!$tableExists) {
                $this->errors[] = "Inventory table does not exist";
                return;
            }
            
            $this->successes[] = "✓ Inventory table exists";
            
            // Test 2: Get all columns
            $stmt = $db->query("SHOW COLUMNS FROM inventory");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnMap = [];
            foreach ($columns as $col) {
                $columnMap[$col['Field']] = $col;
            }
            
            // Test 3: Check easyverein_id column type
            if (isset($columnMap['easyverein_id'])) {
                $type = strtolower($columnMap['easyverein_id']['Type']);
                if (strpos($type, 'int') !== false && strpos($type, 'unsigned') !== false) {
                    $this->successes[] = "✓ easyverein_id is INT UNSIGNED";
                } else {
                    $this->errors[] = "easyverein_id has wrong type: " . $columnMap['easyverein_id']['Type'];
                }
                
                // Check if it's nullable (it should be for flexibility with manual entries)
                if ($columnMap['easyverein_id']['Null'] === 'YES') {
                    $this->successes[] = "✓ easyverein_id is nullable (supports manual entries)";
                } else {
                    $this->warnings[] = "easyverein_id is NOT NULL (may cause issues with manual entries)";
                }
                
                // Check for UNIQUE constraint
                $stmt = $db->query("SHOW INDEX FROM inventory WHERE Column_name = 'easyverein_id' AND Non_unique = 0");
                if ($stmt->fetch()) {
                    $this->successes[] = "✓ easyverein_id has UNIQUE constraint";
                } else {
                    $this->warnings[] = "easyverein_id should have UNIQUE constraint";
                }
            } else {
                $this->errors[] = "easyverein_id column is missing";
            }
            
            // Test 4: Check required columns
            $requiredColumns = [
                'name' => ['type' => 'varchar', 'size' => 255],
                'image_path' => ['type' => 'varchar'],
                'acquisition_date' => ['type' => 'date'],
                'value' => ['type' => 'decimal'],
                'last_synced_at' => ['type' => 'timestamp'],
                'description' => ['type' => 'text'],
                'serial_number' => ['type' => 'varchar'],
                'location' => ['type' => 'varchar']
            ];
            
            foreach ($requiredColumns as $colName => $requirements) {
                if (isset($columnMap[$colName])) {
                    $type = strtolower($columnMap[$colName]['Type']);
                    $requiredType = strtolower($requirements['type']);
                    
                    if (strpos($type, $requiredType) !== false) {
                        $this->successes[] = "✓ Column '{$colName}' exists with correct type";
                        
                        // Check size for varchar columns if specified
                        if (isset($requirements['size']) && $requirements['type'] === 'varchar') {
                            if (strpos($type, "varchar({$requirements['size']})") !== false) {
                                $this->successes[] = "  └─ Correct size: VARCHAR({$requirements['size']})";
                            } else {
                                $this->warnings[] = "  └─ Size mismatch for '{$colName}': expected VARCHAR({$requirements['size']}), got {$type}";
                            }
                        }
                    } else {
                        $this->errors[] = "Column '{$colName}' has wrong type: {$type} (expected {$requiredType})";
                    }
                } else {
                    $this->errors[] = "Required column '{$colName}' is missing";
                }
            }
            
            // Test 5: Verify table engine and charset
            $stmt = $db->query("
                SELECT ENGINE, TABLE_COLLATION 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'inventory'
            ");
            $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tableInfo) {
                if ($tableInfo['ENGINE'] === 'InnoDB') {
                    $this->successes[] = "✓ Table uses InnoDB engine";
                } else {
                    $this->warnings[] = "Table uses {$tableInfo['ENGINE']} (InnoDB recommended)";
                }
                
                if (strpos($tableInfo['TABLE_COLLATION'], 'utf8mb4') !== false) {
                    $this->successes[] = "✓ Table uses utf8mb4 charset";
                } else {
                    $this->warnings[] = "Table uses {$tableInfo['TABLE_COLLATION']} (utf8mb4 recommended)";
                }
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Content database error: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function displayResults(): void {
        echo str_repeat('=', 70) . "\n";
        echo "Test Results\n";
        echo str_repeat('=', 70) . "\n\n";
        
        // Display successes
        if (!empty($this->successes)) {
            echo "SUCCESSES (" . count($this->successes) . "):\n";
            foreach ($this->successes as $success) {
                echo "  {$success}\n";
            }
            echo "\n";
        }
        
        // Display warnings
        if (!empty($this->warnings)) {
            echo "WARNINGS (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ {$warning}\n";
            }
            echo "\n";
        }
        
        // Display errors
        if (!empty($this->errors)) {
            echo "ERRORS (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "  ✗ {$error}\n";
            }
            echo "\n";
        }
        
        // Final summary
        echo str_repeat('=', 70) . "\n";
        if (empty($this->errors)) {
            echo "✓ ALL TESTS PASSED\n";
            if (!empty($this->warnings)) {
                echo "  (with " . count($this->warnings) . " warning(s))\n";
            }
        } else {
            echo "✗ TESTS FAILED\n";
            echo "  Errors: " . count($this->errors) . "\n";
            echo "  Warnings: " . count($this->warnings) . "\n";
        }
        echo str_repeat('=', 70) . "\n\n";
    }
}

// Run the test
try {
    $test = new MigrationTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Throwable $e) {
    echo "\n";
    echo str_repeat('=', 70) . "\n";
    echo "FATAL ERROR\n";
    echo str_repeat('=', 70) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
