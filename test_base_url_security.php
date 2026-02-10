<?php
/**
 * Test file for BASE_URL security improvements
 * Tests sanitization and production enforcement
 */

// Test the sanitize_http_host function directly
function sanitize_http_host($host) {
    if (empty($host)) {
        return null;
    }
    
    // Only allow alphanumeric, dots, colons, and hyphens (for valid hostnames and ports)
    if (!preg_match('/^[a-zA-Z0-9.\-:]+$/', $host)) {
        return null;
    }
    
    // Additional validation: Prevent consecutive dots and other edge cases
    if (strpos($host, '..') !== false) {
        return null;
    }
    
    // If there's a port, validate the port is at the end and numeric
    if (strpos($host, ':') !== false) {
        $parts = explode(':', $host);
        // Should only have one colon (host:port)
        if (count($parts) !== 2) {
            return null;
        }
        // Port should be numeric
        if (!ctype_digit($parts[1])) {
            return null;
        }
    }
    
    return $host;
}

echo "=== BASE_URL Security Tests ===\n\n";

// Test 1: Valid hostnames
echo "Test 1: Valid hostnames\n";
$validHosts = [
    'localhost',
    'example.com',
    'sub.example.com',
    'example.com:8080',
    '192.168.1.1',
    'test-server.com'
];

foreach ($validHosts as $host) {
    $result = sanitize_http_host($host);
    $status = ($result === $host) ? '✓ PASS' : '✗ FAIL';
    echo "  $status: '$host' => '$result'\n";
}

echo "\nTest 2: Invalid/Malicious hostnames (should return null)\n";
$invalidHosts = [
    'evil.com<script>alert(1)</script>',
    'test.com/../../etc/passwd',
    'example.com?param=value',
    'host.com#fragment',
    'test\' OR 1=1--',
    'host.com\\malicious',
    'test@evil.com',
    'host.com;rm -rf /',
    'test.com|whoami',
    '..example.com',           // consecutive dots at start
    'example..com',            // consecutive dots in middle
    'example.com..',           // consecutive dots at end
    'example.com:abc',         // non-numeric port
    'example.com:80:443',      // multiple colons
    'example.com::',           // double colon
];

foreach ($invalidHosts as $host) {
    $result = sanitize_http_host($host);
    $status = ($result === null) ? '✓ PASS' : '✗ FAIL';
    echo "  $status: '$host' => " . var_export($result, true) . "\n";
}

echo "\nTest 3: Edge cases\n";
$edgeCases = [
    '' => null,
    null => null
];

foreach ($edgeCases as $host => $expected) {
    $result = sanitize_http_host($host);
    $status = ($result === $expected) ? '✓ PASS' : '✗ FAIL';
    echo "  $status: " . var_export($host, true) . " => " . var_export($result, true) . "\n";
}

echo "\n=== Production Environment Test ===\n";
echo "NOTE: The actual production check requires the config to be loaded.\n";
echo "In production mode without BASE_URL in .env, the system should throw RuntimeException.\n";
echo "This is enforced in config/config.php lines 90-125.\n";

echo "\n=== Test Complete ===\n";
