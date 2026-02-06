<?php
/**
 * Test for Candidate Role Translation to Anwärter
 * Run with: php tests/test_candidate_role_translation.php
 */

echo "Testing Candidate Role Translation to Anwärter...\n\n";

$testsPassed = 0;
$totalTests = 0;

// Test 1: main_layout.php sidebar profile
echo "=== Test 1: Sidebar Profile (main_layout.php) ===\n";
$totalTests++;
$templateFile = __DIR__ . '/../includes/templates/main_layout.php';
$content = file_get_contents($templateFile);

if (preg_match('/\$roleDisplay = \$role === \'candidate\' \? \'Anwärter\' : ucfirst\(\$role\)/', $content)) {
    echo "✓ Sidebar profile translates candidate to Anwärter\n";
    $testsPassed++;
} else {
    echo "✗ Sidebar profile does not translate candidate role\n";
}

// Test 2: profile.php
echo "\n=== Test 2: User Profile Page (profile.php) ===\n";
$totalTests++;
$profileFile = __DIR__ . '/../pages/auth/profile.php';
$content = file_get_contents($profileFile);

if (preg_match('/\'candidate\' => \'Anwärter\'/', $content)) {
    echo "✓ Profile page includes candidate => Anwärter translation\n";
    $testsPassed++;
} else {
    echo "✗ Profile page does not include candidate translation\n";
}

// Test 3: register.php
echo "\n=== Test 3: Registration Page (register.php) ===\n";
$totalTests++;
$registerFile = __DIR__ . '/../pages/auth/register.php';
$content = file_get_contents($registerFile);

if (preg_match('/\'candidate\' => \'Anwärter\'/', $content)) {
    echo "✓ Registration page includes candidate => Anwärter translation\n";
    $testsPassed++;
} else {
    echo "✗ Registration page does not include candidate translation\n";
}

// Test 4: directory.php
echo "\n=== Test 4: Members Directory (directory.php) ===\n";
$totalTests++;
$directoryFile = __DIR__ . '/../pages/members/directory.php';
$content = file_get_contents($directoryFile);

if (preg_match('/\'candidate\' => \'Anwärter\'/', $content)) {
    echo "✓ Members directory includes candidate => Anwärter translation\n";
    $testsPassed++;
} else {
    echo "✗ Members directory does not include candidate translation\n";
}

// Test 5: MailService.php
echo "\n=== Test 5: Invitation Email (MailService.php) ===\n";
$totalTests++;
$mailServiceFile = __DIR__ . '/../src/MailService.php';
$content = file_get_contents($mailServiceFile);

if (preg_match('/\'candidate\' => \'Anwärter\'/', $content)) {
    echo "✓ MailService includes candidate => Anwärter translation\n";
    $testsPassed++;
} else {
    echo "✗ MailService does not include candidate translation\n";
}

// Test 6: Alumni link visibility
echo "\n=== Test 6: Alumni Link Visibility ===\n";
$totalTests++;
$templateFile = __DIR__ . '/../includes/templates/main_layout.php';
$content = file_get_contents($templateFile);

// Check that Alumni link exists and is NOT wrapped in a role check
if (preg_match('/Alumni-Netzwerk/', $content)) {
    // Now verify it's not within a conditional block by checking the surrounding context
    $lines = explode("\n", $content);
    $alumniLineIndex = -1;
    
    foreach ($lines as $i => $line) {
        if (strpos($line, 'Alumni-Netzwerk') !== false) {
            $alumniLineIndex = $i;
            break;
        }
    }
    
    if ($alumniLineIndex >= 0) {
        // Check 5 lines before the Alumni link for role conditionals
        $isConditional = false;
        for ($j = max(0, $alumniLineIndex - 5); $j < $alumniLineIndex; $j++) {
            if (preg_match('/<\?php if.*user_role.*\):\s*\?>/', $lines[$j])) {
                // Check if the next closing endif is after the Alumni link
                for ($k = $alumniLineIndex + 1; $k < min(count($lines), $alumniLineIndex + 5); $k++) {
                    if (preg_match('/<\?php endif/', $lines[$k])) {
                        $isConditional = true;
                        break;
                    }
                }
                if ($isConditional) break;
            }
        }
        
        if (!$isConditional) {
            echo "✓ Alumni link is visible to all logged-in users (no role restriction)\n";
            $testsPassed++;
        } else {
            echo "✗ Alumni link is restricted by role check\n";
        }
    } else {
        echo "✗ Alumni link line not found\n";
    }
} else {
    echo "✗ Alumni link not found in template\n";
}

// Test 7: Candidate in Members directory access
echo "\n=== Test 7: Candidate Access to Members Directory ===\n";
$totalTests++;
$templateFile = __DIR__ . '/../includes/templates/main_layout.php';
$content = file_get_contents($templateFile);

if (preg_match('/in_array\(\$_SESSION\[\'user_role\'\],\s*\[.*\'candidate\'.*\]\)/', $content)) {
    echo "✓ Candidate role is included in members directory access check\n";
    $testsPassed++;
} else {
    echo "✗ Candidate role is not included in members directory access\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Tests passed: $testsPassed / $totalTests\n";

if ($testsPassed === $totalTests) {
    echo "\n✓ All tests passed! Candidate role translation is complete.\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed. Please review the implementation.\n";
    exit(1);
}
