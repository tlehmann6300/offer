<?php
/**
 * Test that simulates the actual AuthHandler usage pattern
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

echo "Testing AuthHandler Azure OAuth Pattern\n";
echo "========================================\n\n";

// Simulate what AuthHandler::initiateMicrosoftLogin() does
echo "Test: Simulating AuthHandler::initiateMicrosoftLogin() pattern...\n";

try {
    // Create provider (exactly as AuthHandler does)
    $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
        'clientId'     => 'test-client-id',
        'clientSecret' => 'test-client-secret',
        'redirectUri'  => 'https://example.com/callback',
        'tenant'       => 'test-tenant'
    ]);
    
    echo "  ✓ Provider created\n";
    
    // Set scope as array (the fix)
    $provider->scope = ['openid', 'profile', 'email', 'offline_access', 'User.Read'];
    
    echo "  ✓ Scope set as array: " . implode(', ', $provider->scope) . "\n";
    
    // Try to get authorization URL (this internally calls getDefaultScopes())
    $authUrl = $provider->getAuthorizationUrl();
    
    echo "  ✓ Authorization URL generated successfully\n";
    echo "  ✓ URL preview: " . substr($authUrl, 0, 100) . "...\n";
    
    // Verify that scope is in the URL
    if (strpos($authUrl, 'scope=') !== false) {
        echo "  ✓ Scope parameter found in authorization URL\n";
        
        // Extract and decode the scope parameter
        $urlParts = parse_url($authUrl);
        parse_str($urlParts['query'], $query);
        if (isset($query['scope'])) {
            echo "  ✓ Scope value: " . $query['scope'] . "\n";
        }
    }
    
    echo "\n========================================\n";
    echo "✓ All tests passed!\n";
    echo "The fix works correctly!\n";
    echo "========================================\n";
    
} catch (TypeError $e) {
    echo "\n✗ FAIL: TypeError occurred!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ FAIL: Exception occurred!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
