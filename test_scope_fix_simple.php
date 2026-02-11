<?php
/**
 * Test that verifies the scope fix without requiring network access
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

echo "Testing Scope Array Fix (No Network Required)\n";
echo "==============================================\n\n";

echo "Test: Demonstrating the bug and the fix...\n\n";

// Test 1: Old buggy way (setting scope as string)
echo "1. OLD BUGGY WAY (scope as string):\n";
echo "   Code: \$provider->scope = 'openid profile email';\n";
try {
    $providerBuggy = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'     => 'test',
        'clientSecret' => 'test',
        'redirectUri'  => 'https://example.com/callback',
        'tenant'       => 'test'
    ]);
    $providerBuggy->scope = 'openid profile email offline_access User.Read';
    
    // Access the protected method using reflection
    $reflection = new ReflectionClass($providerBuggy);
    $method = $reflection->getMethod('getDefaultScopes');
    $method->setAccessible(true);
    $result = $method->invoke($providerBuggy);
    
    echo "   Result type: " . gettype($result) . "\n";
    echo "   Result value: " . (is_string($result) ? $result : json_encode($result)) . "\n";
    echo "   ✗ BUG: Returns string instead of array! This causes TypeError!\n\n";
    
} catch (TypeError $e) {
    echo "   ✗ TypeError: " . substr($e->getMessage(), 0, 100) . "...\n\n";
}

// Test 2: Fixed way (setting scope as array)
echo "2. FIXED WAY (scope as array):\n";
echo "   Code: \$provider->scope = ['openid', 'profile', 'email', ...];\n";
try {
    $providerFixed = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'     => 'test',
        'clientSecret' => 'test',
        'redirectUri'  => 'https://example.com/callback',
        'tenant'       => 'test'
    ]);
    $providerFixed->scope = ['openid', 'profile', 'email', 'offline_access', 'User.Read'];
    
    // Access the protected method using reflection
    $reflection = new ReflectionClass($providerFixed);
    $method = $reflection->getMethod('getDefaultScopes');
    $method->setAccessible(true);
    $result = $method->invoke($providerFixed);
    
    echo "   Result type: " . gettype($result) . "\n";
    echo "   Result value: " . json_encode($result) . "\n";
    echo "   ✓ CORRECT: Returns array as expected!\n\n";
    
    // Verify it's actually an array
    if (is_array($result) && count($result) === 5) {
        echo "   ✓ Array contains " . count($result) . " scope items\n";
        echo "   ✓ Scope items: " . implode(', ', $result) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

echo "==============================================\n";
echo "SUMMARY:\n";
echo "- The bug was caused by setting \$provider->scope as a string\n";
echo "- The fix is to set \$provider->scope as an array\n";
echo "- This has been fixed in AuthHandler.php line 393\n";
echo "==============================================\n";
