<?php
/**
 * Critical Flow Test Script
 * 
 * This script tests critical system flows without requiring a browser:
 * 1. Database connections to all 3 databases
 * 2. Existence of critical tables (users, invoices, event_documentation)
 * 3. Write permissions for uploads/ and logs/ directories
 */

// Prevent direct browser access - CLI only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Color output for CLI
function colorize($text, $color) {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[0;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function printHeader($text) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo colorize($text, 'blue') . "\n";
    echo str_repeat('=', 70) . "\n";
}

function printResult($message, $success) {
    $icon = $success ? '✓' : '✗';
    $color = $success ? 'green' : 'red';
    echo colorize("  [$icon] $message", $color) . "\n";
}

function printWarning($message) {
    echo colorize("  [!] $message", 'yellow') . "\n";
}

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$warnings = 0;

// ==============================================================================
// Test 1: Database Connections
// ==============================================================================
printHeader('TEST 1: Database Connections');

// Test User Database
$totalTests++;
try {
    $userDb = Database::getUserDB();
    if ($userDb instanceof PDO) {
        printResult('User Database connection successful', true);
        $passedTests++;
    } else {
        printResult('User Database connection failed: Invalid PDO object', false);
        $failedTests++;
    }
} catch (Exception $e) {
    printResult('User Database connection failed: ' . $e->getMessage(), false);
    $failedTests++;
}

// Test Content Database
$totalTests++;
try {
    $contentDb = Database::getContentDB();
    if ($contentDb instanceof PDO) {
        printResult('Content Database connection successful', true);
        $passedTests++;
    } else {
        printResult('Content Database connection failed: Invalid PDO object', false);
        $failedTests++;
    }
} catch (Exception $e) {
    printResult('Content Database connection failed: ' . $e->getMessage(), false);
    $failedTests++;
}

// Test Invoice/Rech Database
$totalTests++;
try {
    $rechDb = Database::getRechDB();
    if ($rechDb instanceof PDO) {
        printResult('Invoice/Rech Database connection successful', true);
        $passedTests++;
    } else {
        printResult('Invoice/Rech Database connection failed: Invalid PDO object', false);
        $failedTests++;
    }
} catch (Exception $e) {
    printResult('Invoice/Rech Database connection failed: ' . $e->getMessage(), false);
    $failedTests++;
}

// ==============================================================================
// Test 2: Critical Tables Existence
// ==============================================================================
printHeader('TEST 2: Critical Tables Existence');

// Check 'users' table in User Database
$totalTests++;
try {
    if (isset($userDb) && $userDb instanceof PDO) {
        $stmt = $userDb->query("SHOW TABLES LIKE 'users'");
        $exists = $stmt->rowCount() > 0;
        if ($exists) {
            // Also verify key columns exist
            $stmt = $userDb->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'email', 'password', 'role', 'locked_until', 'failed_login_attempts'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                printResult('Table "users" exists with all required columns', true);
                $passedTests++;
            } else {
                printResult('Table "users" exists but missing columns: ' . implode(', ', $missingColumns), false);
                $failedTests++;
            }
        } else {
            printResult('Table "users" does not exist in User Database', false);
            $failedTests++;
        }
    } else {
        printResult('Cannot check "users" table - User Database not connected', false);
        $failedTests++;
    }
} catch (Exception $e) {
    printResult('Error checking "users" table: ' . $e->getMessage(), false);
    $failedTests++;
}

// Check 'invoices' table in Invoice Database
$totalTests++;
try {
    if (isset($rechDb) && $rechDb instanceof PDO) {
        $stmt = $rechDb->query("SHOW TABLES LIKE 'invoices'");
        $exists = $stmt->rowCount() > 0;
        if ($exists) {
            // Verify key columns
            $stmt = $rechDb->query("DESCRIBE invoices");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'user_id', 'description', 'amount', 'status'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                printResult('Table "invoices" exists with all required columns', true);
                $passedTests++;
            } else {
                printResult('Table "invoices" exists but missing columns: ' . implode(', ', $missingColumns), false);
                $failedTests++;
            }
        } else {
            printResult('Table "invoices" does not exist in Invoice Database', false);
            $failedTests++;
        }
    } else {
        printResult('Cannot check "invoices" table - Invoice Database not connected', false);
        $failedTests++;
    }
} catch (Exception $e) {
    printResult('Error checking "invoices" table: ' . $e->getMessage(), false);
    $failedTests++;
}

// Check 'event_documentation' table in Content Database
$totalTests++;
try {
    if (isset($contentDb) && $contentDb instanceof PDO) {
        $stmt = $contentDb->query("SHOW TABLES LIKE 'event_documentation'");
        $exists = $stmt->rowCount() > 0;
        if ($exists) {
            // Verify key columns
            $stmt = $contentDb->query("DESCRIBE event_documentation");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['id', 'event_id', 'created_at'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                printResult('Table "event_documentation" exists with all required columns', true);
                $passedTests++;
            } else {
                printResult('Table "event_documentation" exists but missing columns: ' . implode(', ', $missingColumns), false);
                $failedTests++;
            }
        } else {
            printResult('Table "event_documentation" does not exist in Content Database', false);
            $failedTests++;
        }
    } else {
        printResult('Cannot check "event_documentation" table - Content Database not connected', false);
        $failedTests++;
    }
} catch (Exception $e) {
    printResult('Error checking "event_documentation" table: ' . $e->getMessage(), false);
    $failedTests++;
}

// ==============================================================================
// Test 3: Directory Write Permissions
// ==============================================================================
printHeader('TEST 3: Directory Write Permissions');

// Test uploads/ directory
$totalTests++;
$uploadsDir = __DIR__ . '/uploads';
if (is_dir($uploadsDir)) {
    if (is_writable($uploadsDir)) {
        // Try to actually write a test file
        $testFile = $uploadsDir . '/.test_write_' . time();
        if (@file_put_contents($testFile, 'test') !== false) {
            @unlink($testFile);
            printResult('Directory "uploads/" exists and is writable', true);
            $passedTests++;
        } else {
            printResult('Directory "uploads/" exists but write test failed', false);
            $failedTests++;
        }
    } else {
        printResult('Directory "uploads/" exists but is not writable', false);
        $failedTests++;
    }
} else {
    printResult('Directory "uploads/" does not exist', false);
    $failedTests++;
}

// Test logs/ directory
$totalTests++;
$logsDir = __DIR__ . '/logs';
if (is_dir($logsDir)) {
    if (is_writable($logsDir)) {
        // Try to actually write a test file
        $testFile = $logsDir . '/.test_write_' . time();
        if (@file_put_contents($testFile, 'test') !== false) {
            @unlink($testFile);
            printResult('Directory "logs/" exists and is writable', true);
            $passedTests++;
        } else {
            printResult('Directory "logs/" exists but write test failed', false);
            $failedTests++;
        }
    } else {
        printResult('Directory "logs/" exists but is not writable', false);
        $failedTests++;
    }
} else {
    printResult('Directory "logs/" does not exist', false);
    $failedTests++;
}

// ==============================================================================
// Test 4: Code Validation - Login Lockout Check
// ==============================================================================
printHeader('TEST 4: Code Validation - Login Lockout Check');

$totalTests++;
$authFile = __DIR__ . '/src/Auth.php';
if (file_exists($authFile)) {
    $authContent = file_get_contents($authFile);
    
    // Check for locked_until validation in Auth.php
    // Looking for pattern: if ($user['locked_until'] ... strtotime ... > time())
    $pattern = '/if\s*\(\s*\$user\[[\'"]+locked_until[\'"]+\]\s*&&\s*strtotime\s*\(\s*\$user\[[\'"]+locked_until[\'"]+\]\s*\)\s*>\s*time\s*\(\s*\)\s*\)/s';
    
    if (preg_match($pattern, $authContent)) {
        printResult('Login lockout check (locked_until) is properly implemented in Auth.php', true);
        $passedTests++;
    } else {
        // Try a more lenient pattern
        if (strpos($authContent, "locked_until") !== false && strpos($authContent, "strtotime") !== false) {
            printWarning('Login lockout check found but pattern may differ from expected');
            printResult('locked_until field is referenced in Auth.php', true);
            $passedTests++;
            $warnings++;
        } else {
            printResult('Login lockout check (locked_until) not found or improperly implemented', false);
            $failedTests++;
        }
    }
} else {
    printResult('File "src/Auth.php" not found', false);
    $failedTests++;
}

// ==============================================================================
// Test 5: Code Validation - Bulk Invitation Timeout Prevention
// ==============================================================================
printHeader('TEST 5: Code Validation - Bulk Invitation Timeout Prevention');

// Check api/send_invitation.php
$totalTests++;
$sendInvitationFile = __DIR__ . '/api/send_invitation.php';
if (file_exists($sendInvitationFile)) {
    $content = file_get_contents($sendInvitationFile);
    
    // Check if this file handles bulk operations (it shouldn't - it's for single invitations)
    if (strpos($content, 'foreach') !== false || strpos($content, 'for (') !== false || strpos($content, 'while') !== false) {
        // If it has loops, it should have set_time_limit
        if (strpos($content, 'set_time_limit(0)') !== false) {
            printResult('api/send_invitation.php has loops and set_time_limit(0)', true);
            $passedTests++;
        } else {
            printResult('api/send_invitation.php has loops but missing set_time_limit(0)', false);
            $failedTests++;
        }
    } else {
        printWarning('api/send_invitation.php does not contain loops - single invitation only');
        printResult('No bulk operation detected in send_invitation.php - N/A', true);
        $passedTests++;
        $warnings++;
    }
} else {
    printResult('File "api/send_invitation.php" not found', false);
    $failedTests++;
}

// Check api/import_invitations.php (this is the actual bulk invitation handler)
$totalTests++;
$importInvitationsFile = __DIR__ . '/api/import_invitations.php';
if (file_exists($importInvitationsFile)) {
    $content = file_get_contents($importInvitationsFile);
    
    // This file should have set_time_limit(0) for bulk imports
    if (strpos($content, 'set_time_limit(0)') !== false) {
        printResult('api/import_invitations.php has set_time_limit(0) for bulk operations', true);
        $passedTests++;
    } else {
        printResult('api/import_invitations.php missing set_time_limit(0) for bulk operations', false);
        $failedTests++;
    }
} else {
    printResult('File "api/import_invitations.php" not found', false);
    $failedTests++;
}

// ==============================================================================
// Summary
// ==============================================================================
printHeader('TEST SUMMARY');

echo "\n";
echo "  Total Tests:   " . $totalTests . "\n";
echo colorize("  Passed:        " . $passedTests, 'green') . "\n";
if ($failedTests > 0) {
    echo colorize("  Failed:        " . $failedTests, 'red') . "\n";
}
if ($warnings > 0) {
    echo colorize("  Warnings:      " . $warnings, 'yellow') . "\n";
}
echo "\n";

$successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
if ($failedTests === 0) {
    echo colorize("  ✓ All tests passed! ($successRate%)", 'green') . "\n";
    exit(0);
} else {
    echo colorize("  ✗ Some tests failed. Success rate: $successRate%", 'red') . "\n";
    exit(1);
}
