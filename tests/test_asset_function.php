<?php
/**
 * Unit Test for asset() helper function
 * Tests that asset() properly handles BASE_URL and prevents double slashes
 * Run with: php tests/test_asset_function.php
 */

echo "=== Asset Function Unit Test ===\n\n";

// Define BASE_URL for testing
define('BASE_URL', 'http://example.com/offer/');

// Include the helpers file
require_once __DIR__ . '/../includes/helpers.php';

// Test counter
$passed = 0;
$failed = 0;

/**
 * Test helper function
 */
function test($description, $expected, $actual) {
    global $passed, $failed;
    
    if ($expected === $actual) {
        echo "✓ PASS: $description\n";
        echo "  Expected: $expected\n";
        echo "  Got:      $actual\n\n";
        $passed++;
    } else {
        echo "✗ FAIL: $description\n";
        echo "  Expected: $expected\n";
        echo "  Got:      $actual\n\n";
        $failed++;
    }
}

// Test 1: Basic path
test(
    "Basic path without leading slash",
    "http://example.com/offer/assets/css/style.css",
    asset('assets/css/style.css')
);

// Test 2: Path with leading slash
test(
    "Path with leading slash",
    "http://example.com/offer/assets/css/style.css",
    asset('/assets/css/style.css')
);

// Test 3: Path with BASE_URL having trailing slash (rtrim should remove it)
test(
    "BASE_URL with trailing slash should not create double slash",
    "http://example.com/offer/assets/img/logo.png",
    asset('assets/img/logo.png')
);

// Test 4: Empty path
test(
    "Empty path should result in BASE_URL without trailing slash",
    "http://example.com/offer/",
    asset('')
);

// Test 5: Root path
test(
    "Root path",
    "http://example.com/offer/index.php",
    asset('index.php')
);

// Test 6: Deep nested path
test(
    "Deep nested path",
    "http://example.com/offer/pages/admin/users/edit.php",
    asset('pages/admin/users/edit.php')
);

// Test 7: Path with multiple leading slashes
test(
    "Path with multiple leading slashes",
    "http://example.com/offer/assets/css/style.css",
    asset('//assets/css/style.css')
);

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\n❌ Some tests failed!\n";
    exit(1);
} else {
    echo "\n✅ All tests passed!\n";
    exit(0);
}
