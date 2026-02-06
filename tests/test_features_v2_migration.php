<?php
/**
 * Test Script for Features v2 Migration
 * Validates that the security, notification, and project type columns were added successfully
 */

require_once __DIR__ . '/../includes/database.php';

class FeaturesV2MigrationTest {
    private $errors = [];
    private $warnings = [];
    private $successes = [];
    
    public function run(): bool {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "Testing: Features v2 Migration - Security, Notifications, Project Types\n";
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
            
            // Get all columns from users table
            $stmt = $db->query("SHOW COLUMNS FROM users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnMap = [];
            foreach ($columns as $col) {
                $columnMap[$col['Field']] = $col;
            }
            
            // Test 1: Check security columns
            echo "Checking security columns...\n";
            
            // failed_login_attempts
            if (isset($columnMap['failed_login_attempts'])) {
                $type = strtolower($columnMap['failed_login_attempts']['Type']);
                if (strpos($type, 'int') !== false) {
                    $this->successes[] = "✓ failed_login_attempts column exists (type: {$type})";
                    
                    if ($columnMap['failed_login_attempts']['Default'] === '0') {
                        $this->successes[] = "  └─ Default value is 0";
                    } else {
                        $this->warnings[] = "  └─ Default value should be 0, got: " . $columnMap['failed_login_attempts']['Default'];
                    }
                } else {
                    $this->errors[] = "failed_login_attempts has wrong type: {$type} (expected INT)";
                }
            } else {
                $this->errors[] = "failed_login_attempts column is missing";
            }
            
            // locked_until
            if (isset($columnMap['locked_until'])) {
                $type = strtolower($columnMap['locked_until']['Type']);
                if (strpos($type, 'datetime') !== false) {
                    $this->successes[] = "✓ locked_until column exists (type: {$type})";
                    
                    if ($columnMap['locked_until']['Null'] === 'YES') {
                        $this->successes[] = "  └─ Column is nullable";
                    } else {
                        $this->errors[] = "  └─ Column should be nullable";
                    }
                } else {
                    $this->errors[] = "locked_until has wrong type: {$type} (expected DATETIME)";
                }
            } else {
                $this->errors[] = "locked_until column is missing";
            }
            
            // is_locked_permanently
            if (isset($columnMap['is_locked_permanently'])) {
                $type = strtolower($columnMap['is_locked_permanently']['Type']);
                if (strpos($type, 'tinyint') !== false || strpos($type, 'boolean') !== false) {
                    $this->successes[] = "✓ is_locked_permanently column exists (type: {$type})";
                    
                    if ($columnMap['is_locked_permanently']['Default'] === '0') {
                        $this->successes[] = "  └─ Default value is 0";
                    } else {
                        $this->warnings[] = "  └─ Default value should be 0, got: " . $columnMap['is_locked_permanently']['Default'];
                    }
                } else {
                    $this->errors[] = "is_locked_permanently has wrong type: {$type} (expected BOOLEAN/TINYINT)";
                }
            } else {
                $this->errors[] = "is_locked_permanently column is missing";
            }
            
            // Test 2: Check notification preference columns
            echo "Checking notification preference columns...\n";
            
            // notify_new_projects
            if (isset($columnMap['notify_new_projects'])) {
                $type = strtolower($columnMap['notify_new_projects']['Type']);
                if (strpos($type, 'tinyint') !== false || strpos($type, 'boolean') !== false) {
                    $this->successes[] = "✓ notify_new_projects column exists (type: {$type})";
                    
                    if ($columnMap['notify_new_projects']['Default'] === '1') {
                        $this->successes[] = "  └─ Default value is 1 (YES)";
                    } else {
                        $this->errors[] = "  └─ Default value should be 1, got: " . $columnMap['notify_new_projects']['Default'];
                    }
                } else {
                    $this->errors[] = "notify_new_projects has wrong type: {$type} (expected BOOLEAN/TINYINT)";
                }
            } else {
                $this->errors[] = "notify_new_projects column is missing";
            }
            
            // notify_new_events
            if (isset($columnMap['notify_new_events'])) {
                $type = strtolower($columnMap['notify_new_events']['Type']);
                if (strpos($type, 'tinyint') !== false || strpos($type, 'boolean') !== false) {
                    $this->successes[] = "✓ notify_new_events column exists (type: {$type})";
                    
                    if ($columnMap['notify_new_events']['Default'] === '0') {
                        $this->successes[] = "  └─ Default value is 0 (NO)";
                    } else {
                        $this->errors[] = "  └─ Default value should be 0, got: " . $columnMap['notify_new_events']['Default'];
                    }
                } else {
                    $this->errors[] = "notify_new_events has wrong type: {$type} (expected BOOLEAN/TINYINT)";
                }
            } else {
                $this->errors[] = "notify_new_events column is missing";
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
            
            // Test 1: Check if projects table exists
            $stmt = $db->query("SHOW TABLES LIKE 'projects'");
            $tableExists = $stmt->fetch() !== false;
            
            if (!$tableExists) {
                $this->warnings[] = "Projects table does not exist (skipping project type test)";
                echo "\n";
                return;
            }
            
            $this->successes[] = "✓ Projects table exists";
            
            // Test 2: Get all columns
            $stmt = $db->query("SHOW COLUMNS FROM projects");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $columnMap = [];
            foreach ($columns as $col) {
                $columnMap[$col['Field']] = $col;
            }
            
            // Test 3: Check type column
            echo "Checking project type column...\n";
            
            if (isset($columnMap['type'])) {
                $type = $columnMap['type']['Type'];
                
                // Check if it's an ENUM
                if (strpos($type, 'enum') !== false) {
                    $this->successes[] = "✓ type column exists (ENUM)";
                    
                    // Check if it contains 'internal' and 'external'
                    if (strpos($type, 'internal') !== false && strpos($type, 'external') !== false) {
                        $this->successes[] = "  └─ ENUM contains 'internal' and 'external' values";
                    } else {
                        $this->errors[] = "  └─ ENUM should contain 'internal' and 'external' values, got: {$type}";
                    }
                    
                    // Check default value
                    if ($columnMap['type']['Default'] === 'internal') {
                        $this->successes[] = "  └─ Default value is 'internal'";
                    } else {
                        $this->errors[] = "  └─ Default value should be 'internal', got: " . $columnMap['type']['Default'];
                    }
                } else {
                    $this->errors[] = "type column has wrong type: {$type} (expected ENUM)";
                }
            } else {
                $this->errors[] = "type column is missing from projects table";
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
    $test = new FeaturesV2MigrationTest();
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
