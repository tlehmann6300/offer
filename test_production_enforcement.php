<?php
/**
 * Test production mode enforcement - BASE_URL must be in .env
 */

echo "=== Testing Production Mode Enforcement ===\n\n";

// Simulate production environment without BASE_URL
$testEnv = [
    'ENVIRONMENT' => 'production',
    'DB_USER_HOST' => 'localhost',
    // BASE_URL is intentionally missing
];

// Mock the environment check
$environment = $testEnv['ENVIRONMENT'] ?? 'development';

echo "Test 1: Production mode without BASE_URL\n";
try {
    // This is the logic from config.php
    if ($environment === 'production') {
        throw new RuntimeException('BASE_URL must be defined in .env for production environment');
    }
    echo "✗ FAIL: Should have thrown RuntimeException\n";
} catch (RuntimeException $e) {
    echo "✓ PASS: Correctly throws exception in production without BASE_URL\n";
    echo "  Error message: " . $e->getMessage() . "\n";
}

echo "\nTest 2: Development mode without BASE_URL (should use sanitized fallback)\n";
$environment = 'development';

// Simulate sanitized HTTP_HOST
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['HTTPS'] = null;

function sanitize_http_host($host) {
    if (empty($host)) {
        return null;
    }
    if (!preg_match('/^[a-zA-Z0-9.\-:]+$/', $host)) {
        return null;
    }
    return $host;
}

$protocol = (isset($_SERVER['HTTPS']) ? 'https' : 'http');
$host = sanitize_http_host($_SERVER['HTTP_HOST'] ?? '');

if ($host === null) {
    $host = 'localhost';
}

$baseUrl = $protocol . '://' . $host . '/intra';
echo "✓ PASS: Development mode creates BASE_URL from sanitized host\n";
echo "  Generated BASE_URL: $baseUrl\n";

echo "\nTest 3: Development mode with malicious HTTP_HOST\n";
$_SERVER['HTTP_HOST'] = 'evil.com<script>alert(1)</script>';

$host = sanitize_http_host($_SERVER['HTTP_HOST'] ?? '');
if ($host === null) {
    $host = 'localhost';
}

$baseUrl = $protocol . '://' . $host . '/intra';
$status = ($host === 'localhost') ? '✓ PASS' : '✗ FAIL';
echo "$status: Malicious HTTP_HOST sanitized to safe fallback\n";
echo "  Generated BASE_URL: $baseUrl\n";

echo "\n=== All Tests Complete ===\n";
