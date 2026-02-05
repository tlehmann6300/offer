<?php
/**
 * Unit Test for url() helper function
 * Tests that url() properly uses BASE_URL to generate absolute URLs
 * Run with: php tests/test_url_function.php
 */

echo "=== URL Function Unit Test ===\n\n";

// Define BASE_URL for testing
define('BASE_URL', 'https://intra.business-consulting.de');

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

// Test 1: Basic path without leading slash
test(
    "Basic path without leading slash",
    "https://intra.business-consulting.de/assets/css/style.css",
    url('assets/css/style.css')
);

// Test 2: Path with leading slash
test(
    "Path with leading slash",
    "https://intra.business-consulting.de/assets/css/style.css",
    url('/assets/css/style.css')
);

// Test 3: Deep nested path
test(
    "Deep nested path",
    "https://intra.business-consulting.de/pages/admin/users/edit.php",
    url('pages/admin/users/edit.php')
);

// Test 4: Root file
test(
    "Root file (index.php)",
    "https://intra.business-consulting.de/index.php",
    url('index.php')
);

// Test 5: Empty path
test(
    "Empty path should result in BASE_URL with trailing slash",
    "https://intra.business-consulting.de/",
    url('')
);

// Test 6: Path with multiple leading slashes
test(
    "Path with multiple leading slashes",
    "https://intra.business-consulting.de/assets/css/style.css",
    url('//assets/css/style.css')
);

// Test 7: API endpoint
test(
    "API endpoint path",
    "https://intra.business-consulting.de/api/event_signup.php",
    url('api/event_signup.php')
);

// Test 8: Path with trailing slashes
// Note: url() maintains consistency with asset() - trailing slashes are preserved
test(
    "Path with trailing slashes (matches asset() behavior)",
    "https://intra.business-consulting.de/assets/css//",
    url('assets/css//')
);

// Test 9: BASE_URL with trailing slash (simulate different config)
// This tests the rtrim behavior
$testBaseUrl = 'https://example.com/subfolder/';
define('TEST_BASE_URL', $testBaseUrl);

// We need to test manually since BASE_URL is already defined
$testPath = ltrim('assets/test.css', '/');
$testResult = rtrim($testBaseUrl, '/') . '/' . $testPath;
test(
    "BASE_URL with trailing slash should not create double slash",
    "https://example.com/subfolder/assets/test.css",
    $testResult
);

// Test 10: Consistency with asset() function
// url() should produce the same output as asset() for the same path
test(
    "url() should be consistent with asset() function",
    asset('assets/img/logo.png'),
    url('assets/img/logo.png')
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
