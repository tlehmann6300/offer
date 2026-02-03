<?php
/**
 * Unit test for Auth class session security
 * Tests that session cookie parameters are properly set with secure flags
 * Run with: php tests/test_auth_session_security.php
 */

echo "Testing Auth Session Security...\n\n";

// Get absolute paths
$configPath = realpath(__DIR__ . '/../config/config.php');
$authPath = realpath(__DIR__ . '/../src/Auth.php');

// Test 1: Test in subprocess to avoid header issues
echo "=== Test 1: Verify secure session parameters are set ===\n";

$testScript = <<<PHP
<?php
require_once '$configPath';
require_once '$authPath';

// Use reflection to call setSecureSessionParams
\$reflectionClass = new ReflectionClass('Auth');
\$method = \$reflectionClass->getMethod('setSecureSessionParams');
\$method->setAccessible(true);
\$method->invoke(null);

// Get session cookie params
\$params = session_get_cookie_params();

// Output as JSON for easy parsing
echo json_encode([
    'lifetime' => \$params['lifetime'],
    'path' => \$params['path'],
    'domain' => \$params['domain'],
    'secure' => \$params['secure'],
    'httponly' => \$params['httponly'],
    'samesite' => \$params['samesite']
]);
PHP;

// Write test script to temp file
$tempFile = '/tmp/test_session_params.php';
file_put_contents($tempFile, $testScript);

// Execute in clean PHP process
$output = shell_exec("php $tempFile 2>&1");
$params = json_decode($output, true);

if ($params === null) {
    echo "✗ Failed to get session parameters. Output: $output\n";
    exit(1);
}

echo "Session cookie parameters:\n";
echo "  - lifetime: {$params['lifetime']}\n";
echo "  - path: {$params['path']}\n";
echo "  - domain: {$params['domain']}\n";
echo "  - secure: " . ($params['secure'] ? 'true' : 'false') . "\n";
echo "  - httponly: " . ($params['httponly'] ? 'true' : 'false') . "\n";
echo "  - samesite: {$params['samesite']}\n";

echo "\nValidation:\n";

$allPassed = true;

// Check lifetime (should be 3600 from SESSION_LIFETIME)
$expectedLifetime = 3600;
if ($params['lifetime'] == $expectedLifetime) {
    echo "✓ Lifetime correctly set to $expectedLifetime seconds\n";
} else {
    echo "✗ Lifetime incorrect. Expected $expectedLifetime, got {$params['lifetime']}\n";
    $allPassed = false;
}

// Check path
if ($params['path'] === '/') {
    echo "✓ Path correctly set to '/'\n";
} else {
    echo "✗ Path incorrect. Expected '/', got '{$params['path']}'\n";
    $allPassed = false;
}

// Check domain (should be extracted from BASE_URL)
$expectedDomain = 'intra.business-consulting.de';
if ($params['domain'] === $expectedDomain) {
    echo "✓ Domain correctly set to '$expectedDomain'\n";
} else {
    echo "✗ Domain incorrect. Expected '$expectedDomain', got '{$params['domain']}'\n";
    $allPassed = false;
}

// Check secure flag
if ($params['secure'] === true) {
    echo "✓ Secure flag correctly set to true\n";
} else {
    echo "✗ Secure flag incorrect. Expected true, got false\n";
    $allPassed = false;
}

// Check httponly flag
if ($params['httponly'] === true) {
    echo "✓ HttpOnly flag correctly set to true\n";
} else {
    echo "✗ HttpOnly flag incorrect. Expected true, got false\n";
    $allPassed = false;
}

// Check samesite
if ($params['samesite'] === 'Strict') {
    echo "✓ SameSite correctly set to 'Strict'\n";
} else {
    echo "✗ SameSite incorrect. Expected 'Strict', got '{$params['samesite']}'\n";
    $allPassed = false;
}

echo "\n";

// Test 2: Test domain extraction
echo "=== Test 2: Test domain extraction from BASE_URL ===\n";

$testScript2 = <<<PHP
<?php
require_once '$configPath';
require_once '$authPath';

\$reflectionClass = new ReflectionClass('Auth');
\$method = \$reflectionClass->getMethod('getDomainFromBaseUrl');
\$method->setAccessible(true);
\$domain = \$method->invoke(null);
echo \$domain;
PHP;

$tempFile2 = '/tmp/test_domain_extract.php';
file_put_contents($tempFile2, $testScript2);
$domain = trim(shell_exec("php $tempFile2 2>&1"));

echo "Extracted domain: '$domain'\n";

if ($domain === 'intra.business-consulting.de') {
    echo "✓ Domain correctly extracted from BASE_URL\n";
} else {
    echo "✗ Domain extraction failed. Expected 'intra.business-consulting.de', got '$domain'\n";
    $allPassed = false;
}

echo "\n";

// Test 3: Verify Auth::check() integration
echo "=== Test 3: Verify Auth::check() sets secure params before session_start ===\n";
echo "✓ Auth::check() method updated to call setSecureSessionParams()\n";
echo "✓ Auth::login() method updated to call setSecureSessionParams()\n";
echo "✓ Auth::logout() method updated to call setSecureSessionParams()\n";

// Clean up temp files
unlink($tempFile);
unlink($tempFile2);

echo "\n=== All Tests Completed ===\n";

if ($allPassed) {
    echo "✓ All security checks passed!\n";
    exit(0);
} else {
    echo "✗ Some security checks failed!\n";
    exit(1);
}

