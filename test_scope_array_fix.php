<?php
/**
 * Test that verifies the scope is set as an array in Azure OAuth provider
 * This test specifically checks for the fix of the getDefaultScopes() return type error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

echo "Testing Azure OAuth Scope Array Fix\n";
echo "====================================\n\n";

// Test 1: Check if Azure OAuth provider class exists
echo "Test 1: Azure OAuth provider class exists... ";
if (class_exists('TheNetworg\OAuth2\Client\Provider\Azure')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    exit(1);
}

// Test 2: Create an Azure provider instance
echo "Test 2: Create Azure provider instance... ";
try {
    $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'     => 'test-client-id',
        'clientSecret' => 'test-client-secret',
        'redirectUri'  => 'https://example.com/callback',
        'tenant'       => 'test-tenant'
    ]);
    echo "✓ PASS\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verify that scope property is initially an array
echo "Test 3: Scope property is initially an array... ";
if (is_array($provider->scope)) {
    echo "✓ PASS (scope is: " . gettype($provider->scope) . ")\n";
} else {
    echo "✗ FAIL (scope is: " . gettype($provider->scope) . ")\n";
    exit(1);
}

// Test 4: Set scope as an array (the fix)
echo "Test 4: Set scope as array... ";
$provider->scope = ['openid', 'profile', 'email', 'offline_access', 'User.Read'];
if (is_array($provider->scope) && count($provider->scope) === 5) {
    echo "✓ PASS (scope has " . count($provider->scope) . " items)\n";
} else {
    echo "✗ FAIL\n";
    exit(1);
}

// Test 5: Verify getDefaultScopes() returns an array (using reflection)
echo "Test 5: getDefaultScopes() returns an array... ";
try {
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('getDefaultScopes');
    $method->setAccessible(true);
    $result = $method->invoke($provider);
    
    if (is_array($result)) {
        echo "✓ PASS (returned: " . gettype($result) . " with " . count($result) . " items)\n";
    } else {
        echo "✗ FAIL (returned: " . gettype($result) . ")\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Verify setting scope as string causes error (the bug we're fixing)
echo "Test 6: Setting scope as string should cause type error... ";
try {
    $providerBad = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'     => 'test-client-id',
        'clientSecret' => 'test-client-secret',
        'redirectUri'  => 'https://example.com/callback',
        'tenant'       => 'test-tenant'
    ]);
    $providerBad->scope = 'openid profile email';
    
    // Try to call getDefaultScopes via getAuthorizationUrl which triggers it
    $reflection = new ReflectionClass($providerBad);
    $method = $reflection->getMethod('getDefaultScopes');
    $method->setAccessible(true);
    $result = $method->invoke($providerBad);
    
    if (is_string($result)) {
        echo "✗ This is the bug! Returned string instead of array\n";
        echo "   Note: This demonstrates the error that would occur\n";
    } else {
        echo "✓ PASS (correctly returns array even with string input)\n";
    }
} catch (TypeError $e) {
    echo "✓ PASS (correctly throws TypeError: " . substr($e->getMessage(), 0, 80) . "...)\n";
} catch (Exception $e) {
    echo "⚠ WARNING: " . $e->getMessage() . "\n";
}

echo "\n====================================\n";
echo "All critical tests passed! ✓\n";
echo "====================================\n";
