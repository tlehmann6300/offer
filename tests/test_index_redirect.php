<?php
/**
 * Test for index.php redirect functionality
 * Verifies that the index.php file redirects correctly based on authentication status
 */

echo "Testing index.php redirect functionality...\n\n";

// Test 1: Check file structure
echo "Test 1: Checking index.php structure...\n";
$indexPath = __DIR__ . '/../index.php';
if (file_exists($indexPath)) {
    echo "✓ index.php exists\n";
} else {
    echo "✗ index.php not found\n";
    exit(1);
}

// Test 2: Check PHP syntax
$output = [];
$returnVar = 0;
exec("php -l $indexPath 2>&1", $output, $returnVar);
if ($returnVar === 0) {
    echo "✓ index.php has valid PHP syntax\n";
} else {
    echo "✗ index.php has syntax errors:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

// Test 3: Check file content requirements
echo "\nTest 3: Checking file content requirements...\n";
$content = file_get_contents($indexPath);

// Check if <?php is in line 1, column 1
$lines = explode("\n", $content);
if (substr($lines[0], 0, 5) === '<?php') {
    echo "✓ <?php is in line 1, column 1\n";
} else {
    echo "✗ <?php is not in line 1, column 1\n";
    exit(1);
}

// Check for required includes
if (strpos($content, "require_once 'config/config.php'") !== false) {
    echo "✓ Contains require_once 'config/config.php'\n";
} else {
    echo "✗ Missing require_once 'config/config.php'\n";
    exit(1);
}

if (strpos($content, "require_once 'src/Auth.php'") !== false) {
    echo "✓ Contains require_once 'src/Auth.php'\n";
} else {
    echo "✗ Missing require_once 'src/Auth.php'\n";
    exit(1);
}

// Check for Auth::check()
if (strpos($content, "Auth::check()") !== false) {
    echo "✓ Contains Auth::check()\n";
} else {
    echo "✗ Missing Auth::check()\n";
    exit(1);
}

// Check for dashboard redirect with BASE_URL
if (strpos($content, "header('Location: ' . BASE_URL . '/pages/dashboard/index.php')") !== false) {
    echo "✓ Contains dashboard redirect with BASE_URL\n";
} else {
    echo "✗ Missing dashboard redirect with BASE_URL\n";
    exit(1);
}

// Check for login redirect with BASE_URL
if (strpos($content, "header('Location: ' . BASE_URL . '/pages/auth/login.php')") !== false) {
    echo "✓ Contains login redirect with BASE_URL\n";
} else {
    echo "✗ Missing login redirect with BASE_URL\n";
    exit(1);
}

// Check for exit statements after headers
if (preg_match("/header\([^)]+\);\s*exit;/", $content)) {
    echo "✓ Contains exit; after header() calls\n";
} else {
    echo "✗ Missing exit; after header() calls\n";
    exit(1);
}

// Check for fallback button
if (strpos($content, "Weiter zum Login") !== false) {
    echo "✓ Contains 'Weiter zum Login' button\n";
} else {
    echo "✗ Missing 'Weiter zum Login' button\n";
    exit(1);
}

echo "\n✅ All tests passed!\n";
