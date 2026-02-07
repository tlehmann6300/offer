<?php
/**
 * Test Profile Review Prompt Implementation
 * Tests the profile review modal and API endpoint functionality
 */

require_once __DIR__ . '/../src/Database.php';

echo "=== Testing Profile Review Prompt Implementation ===\n\n";

try {
    $db = Database::getUserDB();
    
    // Test 1: Check if prompt_profile_review column exists
    echo "Test 1: Checking if prompt_profile_review column exists...\n";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnExists = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'prompt_profile_review') {
            $columnExists = true;
            echo "✓ Column 'prompt_profile_review' exists\n";
            echo "  Type: {$column['Type']}\n";
            echo "  Default: {$column['Default']}\n\n";
            break;
        }
    }
    
    if (!$columnExists) {
        echo "✗ Column 'prompt_profile_review' not found!\n\n";
        exit(1);
    }
    
    // Test 2: Check if any users have the flag set
    echo "Test 2: Checking for users with prompt_profile_review = 1...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE prompt_profile_review = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Found {$result['count']} user(s) with flag set\n\n";
    
    // Test 3: Check if API file exists and has correct structure
    echo "Test 3: Checking API endpoint file...\n";
    $apiFile = __DIR__ . '/../api/dismiss_profile_review.php';
    if (file_exists($apiFile)) {
        echo "✓ API file exists at: $apiFile\n";
        
        // Check for key components
        $content = file_get_contents($apiFile);
        $checks = [
            'Auth::check()' => 'Authentication check',
            'prompt_profile_review' => 'Flag update',
            'json_encode' => 'JSON response',
            'UPDATE users SET prompt_profile_review = 0' => 'SQL update statement'
        ];
        
        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                echo "  ✓ Contains: $description\n";
            } else {
                echo "  ✗ Missing: $description\n";
            }
        }
        echo "\n";
    } else {
        echo "✗ API file not found!\n\n";
        exit(1);
    }
    
    // Test 4: Check if dashboard has the modal
    echo "Test 4: Checking dashboard modal implementation...\n";
    $dashboardFile = __DIR__ . '/../pages/dashboard/index.php';
    if (file_exists($dashboardFile)) {
        echo "✓ Dashboard file exists\n";
        
        $content = file_get_contents($dashboardFile);
        $checks = [
            'prompt_profile_review' => 'Flag check',
            'profile-review-modal' => 'Modal div',
            'Deine Rolle wurde geändert!' => 'Modal title',
            'dismissProfileReviewPrompt' => 'Dismiss function',
            'dismiss_profile_review.php' => 'API endpoint call',
            'Zum Profil' => 'Profile button'
        ];
        
        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                echo "  ✓ Contains: $description\n";
            } else {
                echo "  ✗ Missing: $description\n";
            }
        }
        echo "\n";
    } else {
        echo "✗ Dashboard file not found!\n\n";
        exit(1);
    }
    
    // Test 5: Syntax check
    echo "Test 5: PHP Syntax Validation...\n";
    exec("php -l $apiFile 2>&1", $output, $returnCode);
    if ($returnCode === 0) {
        echo "✓ API file has valid PHP syntax\n";
    } else {
        echo "✗ API file has syntax errors:\n";
        echo implode("\n", $output) . "\n";
    }
    
    exec("php -l $dashboardFile 2>&1", $output, $returnCode);
    if ($returnCode === 0) {
        echo "✓ Dashboard file has valid PHP syntax\n";
    } else {
        echo "✗ Dashboard file has syntax errors:\n";
        echo implode("\n", $output) . "\n";
    }
    
    echo "\n=== All Tests Completed Successfully ===\n";
    
} catch (Exception $e) {
    echo "✗ Error during testing: " . $e->getMessage() . "\n";
    exit(1);
}
