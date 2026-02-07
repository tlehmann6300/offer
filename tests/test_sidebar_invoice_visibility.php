<?php
/**
 * Test for Sidebar Invoice Module Visibility
 * Tests that the main_layout.php correctly uses hasRole() function for invoice visibility
 * Run with: php tests/test_sidebar_invoice_visibility.php
 */

echo "Testing Sidebar Invoice Visibility Integration...\n\n";

// Get absolute paths
$configPath = realpath(__DIR__ . '/../config/config.php');
$mainLayoutPath = realpath(__DIR__ . '/../includes/templates/main_layout.php');

if (!$configPath || !$mainLayoutPath) {
    echo "❌ FAILED: Could not find required files\n";
    echo "Config path: $configPath\n";
    echo "Main layout path: $mainLayoutPath\n";
    exit(1);
}

// Test 1: Check that main_layout.php requires AuthHandler
echo "=== Test 1: Verify AuthHandler is required in main_layout.php ===\n";
$layoutContent = file_get_contents($mainLayoutPath);
if (strpos($layoutContent, "require_once __DIR__ . '/../handlers/AuthHandler.php'") !== false) {
    echo "✓ PASSED: AuthHandler is required in main_layout.php\n\n";
} else {
    echo "❌ FAILED: AuthHandler is not required in main_layout.php\n";
    exit(1);
}

// Test 2: Check that invoices link uses hasRole() function
echo "=== Test 2: Verify invoices link uses hasRole() function ===\n";
if (strpos($layoutContent, "AuthHandler::hasRole('board')") !== false && 
    strpos($layoutContent, "AuthHandler::hasRole('head')") !== false && 
    strpos($layoutContent, "AuthHandler::hasRole('alumni_board')") !== false) {
    echo "✓ PASSED: Invoices link uses hasRole() for all three roles\n\n";
} else {
    echo "❌ FAILED: Invoices link does not use hasRole() correctly\n";
    exit(1);
}

// Test 3: Check that active state uses strpos with PHP_SELF
echo "=== Test 3: Verify active state uses strpos with PHP_SELF ===\n";
if (strpos($layoutContent, "strpos(\$_SERVER['PHP_SELF'], 'invoices')") !== false) {
    echo "✓ PASSED: Active state uses strpos with \$_SERVER['PHP_SELF']\n\n";
} else {
    echo "❌ FAILED: Active state does not use strpos correctly\n";
    exit(1);
}

// Test 4: Check that confirmation message is present
echo "=== Test 4: Verify confirmation message is present ===\n";
if (strpos($layoutContent, "✅ Sidebar updated: Invoices visible for Board, Head & Alumni Board") !== false) {
    echo "✓ PASSED: Confirmation message is present\n\n";
} else {
    echo "❌ FAILED: Confirmation message is not present\n";
    exit(1);
}

// Test 5: Verify the link points to pages/invoices/index.php
echo "=== Test 5: Verify link points to correct invoice page ===\n";
if (strpos($layoutContent, "pages/invoices/index.php") !== false) {
    echo "✓ PASSED: Link points to pages/invoices/index.php\n\n";
} else {
    echo "❌ FAILED: Link does not point to correct page\n";
    exit(1);
}

// Test 6: Verify the icon is correct (fas fa-file-invoice-dollar)
echo "=== Test 6: Verify invoice icon is correct ===\n";
if (strpos($layoutContent, "fas fa-file-invoice-dollar") !== false) {
    echo "✓ PASSED: Icon uses fas fa-file-invoice-dollar\n\n";
} else {
    echo "❌ FAILED: Icon is not correct\n";
    exit(1);
}

// Test 7: Verify the label is 'Rechnungen'
echo "=== Test 7: Verify label is 'Rechnungen' ===\n";
if (preg_match('/<span>Rechnungen<\/span>/i', $layoutContent)) {
    echo "✓ PASSED: Label is 'Rechnungen'\n\n";
} else {
    echo "❌ FAILED: Label is not 'Rechnungen'\n";
    exit(1);
}

echo "===========================================\n";
echo "All tests passed! ✓\n";
echo "===========================================\n";
