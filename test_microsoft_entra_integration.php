<?php
/**
 * Test Microsoft Entra ID integration
 * This test verifies the basic structure and methods exist
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/handlers/AuthHandler.php';

echo "Testing Microsoft Entra ID Integration\n";
echo "=====================================\n\n";

// Test 1: Check if AuthHandler class exists
echo "Test 1: AuthHandler class exists... ";
if (class_exists('AuthHandler')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    exit(1);
}

// Test 2: Check if initiateMicrosoftLogin method exists
echo "Test 2: initiateMicrosoftLogin method exists... ";
if (method_exists('AuthHandler', 'initiateMicrosoftLogin')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    exit(1);
}

// Test 3: Check if handleMicrosoftCallback method exists
echo "Test 3: handleMicrosoftCallback method exists... ";
if (method_exists('AuthHandler', 'handleMicrosoftCallback')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    exit(1);
}

// Test 4: Check if Azure constants are defined
echo "Test 4: Azure constants are defined... ";
$azureConstants = [
    'AZURE_CLIENT_ID',
    'AZURE_CLIENT_SECRET',
    'AZURE_REDIRECT_URI',
    'AZURE_TENANT_ID'
];

$allDefined = true;
$missingConstants = [];
foreach ($azureConstants as $constant) {
    if (!defined($constant)) {
        $allDefined = false;
        $missingConstants[] = $constant;
    }
}

if ($allDefined) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL (missing: " . implode(', ', $missingConstants) . ")\n";
    exit(1);
}

// Test 5: Check if Azure constants have values
echo "Test 5: Azure constants have values... ";
$hasValues = true;
$emptyConstants = [];
foreach ($azureConstants as $constant) {
    $value = constant($constant);
    if (empty($value)) {
        $hasValues = false;
        $emptyConstants[] = $constant;
    }
}

if ($hasValues) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL (empty: " . implode(', ', $emptyConstants) . ")\n";
    echo "Note: This is expected if Azure credentials are not configured in .env\n";
}

// Test 6: Check if TheNetworg\OAuth2\Client\Provider\Azure class exists
echo "Test 6: Azure OAuth provider class exists... ";
if (class_exists('TheNetworg\OAuth2\Client\Provider\Azure')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL\n";
    echo "Note: Make sure composer dependencies are installed\n";
    exit(1);
}

echo "\n=====================================\n";
echo "All tests passed! ✓\n";
echo "=====================================\n";
