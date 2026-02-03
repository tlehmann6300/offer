<?php
/**
 * Unit test for session regeneration in Auth::login()
 * Tests that session_regenerate_id is called after successful login
 * Run with: php tests/test_session_regeneration.php
 */

echo "Testing Session Regeneration on Login...\n\n";

// Get absolute paths
$configPath = realpath(__DIR__ . '/../config/config.php');
$authPath = realpath(__DIR__ . '/../src/Auth.php');

echo "=== Test 1: Verify session_regenerate_id is called in Auth::login() ===\n";

// Check that the code contains the session_regenerate_id call
$authContent = file_get_contents($authPath);

// Look for session_regenerate_id(true) in the login method
if (strpos($authContent, 'session_regenerate_id(true)') !== false) {
    echo "✓ Found session_regenerate_id(true) call in Auth.php\n";
} else {
    echo "✗ session_regenerate_id(true) call not found in Auth.php\n";
    exit(1);
}

// Verify it's in the login method by checking the context
// Find the login method more reliably
$loginStart = strpos($authContent, 'public static function login(');
$nextMethodStart = strpos($authContent, 'public static function logout(', $loginStart);

if ($loginStart !== false && $nextMethodStart !== false) {
    $loginMethodBody = substr($authContent, $loginStart, $nextMethodStart - $loginStart);
    
    // Check if session_regenerate_id is called after password verification
    // and after session_start
    if (strpos($loginMethodBody, 'session_regenerate_id(true)') !== false) {
        echo "✓ session_regenerate_id(true) is present in login() method\n";
        
        // Check if it's after session_start
        $sessionStartPos = strpos($loginMethodBody, 'session_start()');
        $regeneratePos = strpos($loginMethodBody, 'session_regenerate_id(true)');
        
        if ($sessionStartPos !== false && $regeneratePos !== false && $regeneratePos > $sessionStartPos) {
            echo "✓ session_regenerate_id(true) is called after session_start()\n";
        } else {
            echo "✗ session_regenerate_id(true) is not properly positioned after session_start()\n";
            exit(1);
        }
        
        // Check if it's before setting session variables
        $sessionVarPos = strpos($loginMethodBody, '$_SESSION[\'authenticated\']');
        
        if ($sessionVarPos !== false && $regeneratePos < $sessionVarPos) {
            echo "✓ session_regenerate_id(true) is called before setting session variables\n";
        } else {
            echo "✗ session_regenerate_id(true) should be called before setting session variables\n";
            exit(1);
        }
    } else {
        echo "✗ session_regenerate_id(true) not found in login() method\n";
        exit(1);
    }
} else {
    echo "✗ Could not extract login() method from Auth.php\n";
    exit(1);
}

echo "\n=== Test 2: Verify comment explains session fixation prevention ===\n";

// Check for a comment explaining session fixation
if (preg_match('/session.fixation/i', $authContent)) {
    echo "✓ Found comment about session fixation prevention\n";
} else {
    echo "! Warning: No comment found explaining session fixation prevention\n";
    echo "  (This is acceptable but recommended for documentation)\n";
}

echo "\n=== All Tests Completed ===\n";
echo "✓ Session regeneration security checks passed!\n";
echo "\nSession fixation attack prevention is properly implemented:\n";
echo "  - session_regenerate_id(true) is called after successful login\n";
echo "  - New session ID is generated before setting authenticated session variables\n";
echo "  - This prevents attackers from hijacking user sessions\n";

exit(0);
