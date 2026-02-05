<?php
/**
 * Test to verify inventory add page properly warns about EasyVerein and disables form for non-admins
 * This test validates the requirements from the issue
 * 
 * Run from repository root with: php tests/test_inventory_add_easyverein_protection.php
 */

echo "Testing Inventory Add EasyVerein Protection...\n\n";

$addPagePath = realpath(__DIR__ . '/../pages/inventory/add.php');

if ($addPagePath === false || !file_exists($addPagePath)) {
    echo "✗ Error: Could not find add.php file\n";
    echo "  Expected location: " . __DIR__ . "/../pages/inventory/add.php\n";
    exit(1);
}

// Test 1: Verify isAdmin check exists
echo "=== Test 1: Verify isAdmin check ===\n";

$content = file_get_contents($addPagePath);

if (strpos($content, '$isAdmin = isset($_SESSION[\'user_role\']) && $_SESSION[\'user_role\'] === \'admin\';') !== false) {
    echo "✓ Found isAdmin check for admin role\n";
} else {
    echo "✗ Missing isAdmin check\n";
    exit(1);
}

// Test 2: Verify warning banner exists
echo "\n=== Test 2: Verify warning banner for non-admin users ===\n";

if (strpos($content, 'Neue Artikel müssen zuerst in EasyVerein erstellt und dann synchronisiert werden.') !== false) {
    echo "✓ Found warning banner message\n";
} else {
    echo "✗ Missing warning banner\n";
    exit(1);
}

if (preg_match('/if\s*\(\s*!\$isAdmin\s*\).*?bg-yellow-50/s', $content)) {
    echo "✓ Warning banner is conditionally displayed for non-admin users\n";
} else {
    echo "✗ Warning banner conditional display not found\n";
    exit(1);
}

if (strpos($content, 'EasyVerein Synchronisation erforderlich') !== false) {
    echo "✓ Found warning banner title\n";
} else {
    echo "✗ Missing warning banner title\n";
    exit(1);
}

// Test 3: Verify link to sync page exists
echo "\n=== Test 3: Verify sync page link ===\n";

if (preg_match('/href=["\']sync\.php["\']/', $content)) {
    echo "✓ Found link to sync.php in warning banner\n";
} else {
    echo "✗ Missing link to sync.php\n";
    exit(1);
}

// Test 4: Verify form submission is disabled for non-admin
echo "\n=== Test 4: Verify form submission protection ===\n";

// Check backend protection
if (preg_match('/if\s*\(\s*!\$isAdmin\s*\).*?\$error\s*=.*?Neue Artikel.*?EasyVerein/s', $content)) {
    echo "✓ Found backend form submission protection for non-admin users\n";
} else {
    echo "✗ Missing backend form submission protection\n";
    exit(1);
}

// Check frontend protection (onsubmit)
if (preg_match('/<form[^>]*onsubmit=["\']return false;["\'][^>]*>/s', $content) || 
    preg_match('/<form[^>]*\?php if \(\!\$isAdmin\)\: \?\>onsubmit=["\']return false;["\']/s', $content)) {
    echo "✓ Found frontend form submission protection (onsubmit)\n";
} else {
    echo "✗ Missing frontend form submission protection\n";
    exit(1);
}

// Test 5: Verify disabled attributes
echo "\n=== Test 5: Verify form fields are disabled for non-admin ===\n";

if (strpos($content, '$disabledAttr = !$isAdmin ? \'disabled\' : \'\';') !== false) {
    echo "✓ Found disabledAttr variable\n";
} else {
    echo "✗ Missing disabledAttr variable\n";
    exit(1);
}

if (strpos($content, '$readonlyClass = !$isAdmin ? \'bg-gray-100 cursor-not-allowed\' : \'\';') !== false) {
    echo "✓ Found readonlyClass variable\n";
} else {
    echo "✗ Missing readonlyClass variable\n";
    exit(1);
}

// Check that disabled attribute is applied to inputs
$disabledCount = substr_count($content, '$disabledAttr');
if ($disabledCount >= 10) {  // Should be applied to multiple fields
    echo "✓ Found disabled attribute applied to form fields (count: $disabledCount)\n";
} else {
    echo "✗ Disabled attribute not sufficiently applied to form fields (count: $disabledCount)\n";
    exit(1);
}

// Test 6: Verify submit button is disabled
echo "\n=== Test 6: Verify submit button is disabled ===\n";

// Check if submit button uses $disabledAttr variable - need to check across lines
$lines = explode("\n", $content);
$foundSubmitButton = false;
$hasDisabledAttr = false;

foreach ($lines as $lineNum => $line) {
    if (strpos($line, 'type="submit"') !== false || strpos($line, "type='submit'") !== false) {
        $foundSubmitButton = true;
        // Check this line and the next few lines for $disabledAttr
        for ($i = 0; $i < 3; $i++) {
            if (isset($lines[$lineNum + $i]) && strpos($lines[$lineNum + $i], '$disabledAttr') !== false) {
                $hasDisabledAttr = true;
                break;
            }
        }
        break;
    }
}

if ($foundSubmitButton && $hasDisabledAttr) {
    echo "✓ Submit button has disabled attribute\n";
} else if ($foundSubmitButton) {
    echo "✗ Submit button missing disabled attribute\n";
    exit(1);
} else {
    echo "✗ Submit button not found\n";
    exit(1);
}

// Test 7: Verify admin bypass message
echo "\n=== Test 7: Verify admin bypass indication ===\n";

if (strpos($content, 'Administrator-Bypass aktiv') !== false) {
    echo "✓ Found admin bypass indication message\n";
} else {
    echo "✗ Missing admin bypass indication\n";
    exit(1);
}

echo "\n=== All Tests Passed! ===\n";
echo "✓ Inventory add page properly warns about EasyVerein synchronization\n";
echo "✓ Warning banner is displayed for non-admin users\n";
echo "✓ Form fields are disabled for non-admin users\n";
echo "✓ Form submission is blocked both on frontend and backend\n";
echo "✓ Admin users can bypass the restriction\n";

exit(0);
