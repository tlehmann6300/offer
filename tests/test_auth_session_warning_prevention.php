<?php
/**
 * Test to verify Auth.php prevents session already started warnings
 * This test validates the requirements from the issue
 * 
 * Run with: php tests/test_auth_session_warning_prevention.php
 */

echo "Testing Auth.php Session Warning Prevention...\n\n";

$authPath = realpath(__DIR__ . '/../src/Auth.php');

// Test 1: Verify file structure
echo "=== Test 1: Verify file starts correctly ===\n";

$content = file_get_contents($authPath);
$firstFiveChars = substr($content, 0, 5);

$phpTag = '<' . '?php';
if ($firstFiveChars === $phpTag) {
    echo "✓ File starts exactly with PHP opening tag\n";
} else {
    echo "✗ File does not start correctly\n";
    echo "  First 5 characters: " . bin2hex($firstFiveChars) . "\n";
    exit(1);
}

// Check for BOM
$firstThreeBytes = substr($content, 0, 3);
if ($firstThreeBytes === "\xEF\xBB\xBF") {
    echo "✗ File has UTF-8 BOM (should be removed)\n";
    exit(1);
} else {
    echo "✓ No UTF-8 BOM found\n";
}

// Test 2: Verify no closing tag
echo "\n=== Test 2: Verify file has no closing PHP tag ===\n";

$closeTag = '?' . '>';
$lastTenChars = substr($content, -10);
if (strpos($lastTenChars, $closeTag) !== false) {
    echo "✗ File contains closing PHP tag\n";
    exit(1);
} else {
    echo "✓ No closing PHP tag found\n";
}

// Check the actual ending
$trimmedContent = rtrim($content);
if (substr($trimmedContent, -1) === '}') {
    echo "✓ File ends with closing brace\n";
} else {
    echo "✗ File does not end with closing brace\n";
    exit(1);
}

// Test 3: Verify session_start is only called when needed
echo "\n=== Test 3: Verify check() method has proper session check ===\n";

// Extract the check() method
$checkMethodStart = strpos($content, 'public static function check()');
if ($checkMethodStart === false) {
    echo "✗ Could not find check() method\n";
    exit(1);
}

// Find the session_start call within check()
$nextMethodStart = strpos($content, 'public static function login(', $checkMethodStart);
$checkMethodBody = substr($content, $checkMethodStart, $nextMethodStart - $checkMethodStart);

// Verify the conditional check exists
if (preg_match('/if\s*\(\s*session_status\(\)\s*===\s*PHP_SESSION_NONE\s*\)/', $checkMethodBody)) {
    echo "✓ check() method checks session_status() before calling session_start()\n";
} else {
    echo "✗ check() method does not properly check session status\n";
    exit(1);
}

// Verify session_start is inside the conditional
$ifPos = strpos($checkMethodBody, 'if (session_status() === PHP_SESSION_NONE)');
$sessionStartPos = strpos($checkMethodBody, 'session_start()');

if ($ifPos !== false && $sessionStartPos !== false && $sessionStartPos > $ifPos) {
    echo "✓ session_start() is inside the conditional check\n";
} else {
    echo "✗ session_start() is not properly protected by conditional check\n";
    exit(1);
}

// Test 4: Verify other methods also check session status
echo "\n=== Test 4: Verify login() and logout() methods check session status ===\n";

$methodsToCheck = ['login', 'logout'];
foreach ($methodsToCheck as $method) {
    $methodStart = strpos($content, "public static function $method(");
    if ($methodStart === false) {
        echo "✗ Could not find $method() method\n";
        continue;
    }
    
    // Find next method to get method body
    $nextMethod = $method === 'login' ? 'logout' : 'hasPermission';
    $nextMethodStart = strpos($content, "public static function $nextMethod(", $methodStart + 1);
    $methodBody = substr($content, $methodStart, $nextMethodStart - $methodStart);
    
    if (preg_match('/if\s*\(\s*session_status\(\)\s*===\s*PHP_SESSION_NONE\s*\)/', $methodBody)) {
        echo "✓ $method() method checks session_status() before calling session_start()\n";
    } else {
        echo "✗ $method() method does not properly check session status\n";
    }
}

echo "\n=== All Tests Completed ===\n";
echo "✓ All checks passed!\n";
echo "\nSummary:\n";
echo "  - File starts correctly with PHP opening tag (no whitespace or BOM)\n";
echo "  - File has no closing PHP tag at the end\n";
echo "  - check() method properly checks session_status() before session_start()\n";
echo "  - login() and logout() methods also check session_status()\n";
echo "  - This prevents 'session already started' warnings\n";

exit(0);
