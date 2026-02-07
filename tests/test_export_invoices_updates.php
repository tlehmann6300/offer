<?php
/**
 * Test: Export Invoices API - Authentication and Filename
 */

echo "Testing export_invoices.php updates\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Check authentication includes alumni_board
echo "Test 1: Checking authentication includes alumni_board...\n";
$content = file_get_contents(__DIR__ . '/../api/export_invoices.php');

if (strpos($content, "'alumni_board'") !== false) {
    echo "✓ PASS: alumni_board role is included in authentication\n";
} else {
    echo "✗ FAIL: alumni_board role is not found in authentication\n";
    exit(1);
}

// Test 2: Check if authentication check includes board
if (strpos($content, "'board'") !== false) {
    echo "✓ PASS: board role is included in authentication\n";
} else {
    echo "✗ FAIL: board role is not found in authentication\n";
    exit(1);
}

// Test 3: Check if ZIP filename uses German 'rechnungen'
echo "\nTest 2: Checking ZIP filename uses German 'rechnungen'...\n";
if (strpos($content, "rechnungen_export_") !== false) {
    echo "✓ PASS: ZIP filename uses 'rechnungen_export_'\n";
} else {
    echo "✗ FAIL: ZIP filename does not use 'rechnungen_export_'\n";
    exit(1);
}

// Test 4: Check that old 'invoices_export_' is not used
echo "\nTest 3: Checking old 'invoices_export_' is not used...\n";
if (strpos($content, "invoices_export_") === false) {
    echo "✓ PASS: Old 'invoices_export_' is not present\n";
} else {
    echo "✗ FAIL: Old 'invoices_export_' is still present\n";
    exit(1);
}

// Test 5: Check that ZipArchive is used
echo "\nTest 4: Checking ZipArchive is used...\n";
if (strpos($content, "ZipArchive") !== false) {
    echo "✓ PASS: ZipArchive is used\n";
} else {
    echo "✗ FAIL: ZipArchive is not found\n";
    exit(1);
}

// Test 6: Check file iteration logic exists
echo "\nTest 5: Checking invoice iteration logic...\n";
if (strpos($content, "foreach") !== false && strpos($content, "invoices") !== false) {
    echo "✓ PASS: Invoice iteration logic is present\n";
} else {
    echo "✗ FAIL: Invoice iteration logic not found\n";
    exit(1);
}

// Test 7: Check force download headers are set
echo "\nTest 6: Checking force download headers...\n";
if (strpos($content, "Content-Disposition: attachment") !== false) {
    echo "✓ PASS: Force download headers are set\n";
} else {
    echo "✗ FAIL: Force download headers not found\n";
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "All tests passed!\n";
