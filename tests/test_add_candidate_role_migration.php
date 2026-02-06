<?php
/**
 * Test Script for Add Candidate Role Migration
 * Validates that the candidate role was added successfully
 */

require_once __DIR__ . '/../includes/database.php';

class AddCandidateRoleMigrationTest {
    private $errors = [];
    private $warnings = [];
    private $successes = [];
    
    public function run(): bool {
        echo "\n" . str_repeat('=', 70) . "\n";
        echo "Testing: Add Candidate Role Migration\n";
        echo str_repeat('=', 70) . "\n\n";
        
        // Test user database changes
        $this->testUserDatabaseChanges();
        
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
                echo "\n";
                return;
            }
            
            echo "Found role column with type: " . $roleColumn['Type'] . "\n";
            
            // Test 2: Check if candidate role exists
            if (strpos($roleColumn['Type'], "'candidate'") !== false) {
                $this->successes[] = "✓ Users table has 'candidate' role in ENUM";
            } else {
                $this->errors[] = "Users table 'role' ENUM missing 'candidate' value";
                $this->warnings[] = "Current ENUM: " . $roleColumn['Type'];
            }
            
            // Test 3: Check expected roles exist
            $expectedRoles = ['admin', 'board', 'head', 'member', 'alumni', 'candidate'];
            foreach ($expectedRoles as $role) {
                if (strpos($roleColumn['Type'], "'{$role}'") !== false) {
                    $this->successes[] = "✓ Role '{$role}' exists in ENUM";
                } else {
                    $this->warnings[] = "Role '{$role}' not found in ENUM";
                }
            }
            
            // Test 4: Check default value
            if ($roleColumn['Default'] === 'member') {
                $this->successes[] = "✓ Default role is 'member'";
            } else {
                $this->errors[] = "Default role should be 'member', got: " . $roleColumn['Default'];
            }
            
            // Test 5: Check NOT NULL constraint
            if ($roleColumn['Null'] === 'NO') {
                $this->successes[] = "✓ Role column is NOT NULL";
            } else {
                $this->warnings[] = "Role column should be NOT NULL";
            }
            
        } catch (Exception $e) {
            $this->errors[] = "User database error: " . $e->getMessage();
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
    $test = new AddCandidateRoleMigrationTest();
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
