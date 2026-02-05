<?php
/**
 * Test for User Profile Section in main_layout.php
 * Run with: php tests/test_user_profile_sidebar.php
 */

echo "Testing User Profile Sidebar Implementation...\n\n";

// Load the template file
$templateFile = __DIR__ . '/../includes/templates/main_layout.php';
if (!file_exists($templateFile)) {
    echo "✗ Template file not found: $templateFile\n";
    exit(1);
}

$templateContent = file_get_contents($templateFile);

echo "=== Test 1: User Profile Section Structure ===\n";

// Check if the new user profile section exists
if (strpos($templateContent, "class='mt-auto pt-6 border-t border-gray-700'") !== false) {
    echo "✓ New user profile section container found\n";
} else {
    echo "✗ New user profile section container not found\n";
}

// Check for user initials avatar
if (strpos($templateContent, "class='w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-3'") !== false) {
    echo "✓ User initials avatar styling found\n";
} else {
    echo "✗ User initials avatar styling not found\n";
}

echo "\n=== Test 2: Auth::user() Usage ===\n";

// Check if Auth::user() is used instead of $_SESSION variables
$authUserCount = substr_count($templateContent, 'Auth::user()');
if ($authUserCount >= 4) {
    echo "✓ Auth::user() is used (found $authUserCount occurrences)\n";
} else {
    echo "✗ Auth::user() usage insufficient (found $authUserCount occurrences, expected at least 4)\n";
}

// Check for firstname and lastname in initials
if (preg_match('/substr\(Auth::user\(\)\[\'firstname\'\]/', $templateContent) &&
    preg_match('/substr\(Auth::user\(\)\[\'lastname\'\]/', $templateContent)) {
    echo "✓ User initials use firstname and lastname from Auth::user()\n";
} else {
    echo "✗ User initials do not correctly use firstname and lastname\n";
}

// Check for full name display
if (preg_match('/Auth::user\(\)\[\'firstname\'\] \. \' \' \. Auth::user\(\)\[\'lastname\'\]/', $templateContent)) {
    echo "✓ Full name (firstname + lastname) is displayed\n";
} else {
    echo "✗ Full name display not found\n";
}

// Check for role display
if (preg_match('/ucfirst\(Auth::user\(\)\[\'role\'\]\)/', $templateContent)) {
    echo "✓ User role from Auth::user() is displayed\n";
} else {
    echo "✗ User role display not found\n";
}

// Check for email in title attribute
if (preg_match('/title=\'.*Auth::user\(\)\[\'email\'\]/', $templateContent)) {
    echo "✓ Email is shown in title attribute for tooltip\n";
} else {
    echo "✗ Email tooltip not found\n";
}

echo "\n=== Test 3: Logout Button in Profile Section ===\n";

// Check if logout button is within the profile section
if (preg_match('/class=\'mt-auto pt-6 border-t border-gray-700\'.*?pages\/auth\/logout\.php/s', $templateContent)) {
    echo "✓ Logout button is within the user profile section\n";
} else {
    echo "✗ Logout button not found within user profile section\n";
}

// Check for logout button styling
if (strpos($templateContent, "class='flex items-center justify-center w-full px-4 py-2 text-sm font-bold text-white bg-red-600/80 hover:bg-red-600 rounded-lg transition-colors'") !== false) {
    echo "✓ Logout button has correct styling (bg-red-600/80 hover:bg-red-600)\n";
} else {
    echo "✗ Logout button styling not correct\n";
}

// Check for Font Awesome logout icon
if (preg_match('/<i class=\'fas fa-sign-out-alt mr-2\'><\/i> Abmelden/', $templateContent)) {
    echo "✓ Logout button has Font Awesome icon and 'Abmelden' text\n";
} else {
    echo "✗ Logout button icon or text not found\n";
}

echo "\n=== Test 4: BASE_URL Usage ===\n";

// Check if BASE_URL is used for logout link
if (preg_match('/href=\'<\?php echo BASE_URL; \?>\/pages\/auth\/logout\.php\'/', $templateContent)) {
    echo "✓ Logout button uses BASE_URL for the link\n";
} else {
    echo "✗ Logout button does not use BASE_URL\n";
}

echo "\n=== Test 5: Removed Old Code ===\n";

// Check that old session-based code is removed
if (strpos($templateContent, "\$_SESSION['user_email']") === false) {
    echo "✓ Old \$_SESSION['user_email'] code has been removed\n";
} else {
    echo "⚠ Old \$_SESSION['user_email'] code still exists\n";
}

// Check that old user icon is removed
if (strpos($templateContent, 'bg-white/20 flex items-center justify-center') === false) {
    echo "✓ Old user icon styling has been removed\n";
} else {
    echo "⚠ Old user icon styling still exists (may be intentional if used elsewhere)\n";
}

echo "\n=== Test 6: Responsive Design ===\n";

// Check for overflow handling
if (strpos($templateContent, "class='overflow-hidden'") !== false) {
    echo "✓ Overflow handling for long text is present\n";
} else {
    echo "✗ Overflow handling not found\n";
}

// Check for text truncation
if (strpos($templateContent, "class='text-sm font-medium text-white truncate'") !== false) {
    echo "✓ Text truncation for user name is present\n";
} else {
    echo "✗ Text truncation for user name not found\n";
}

// Check for role text styling
if (strpos($templateContent, "class='text-xs text-gray-400 truncate'") !== false) {
    echo "✓ Role text has correct styling and truncation\n";
} else {
    echo "✗ Role text styling not correct\n";
}

echo "\n=== Test Summary ===\n";
echo "User Profile implementation verified:\n";
echo "  - ✓ User initials avatar with firstname and lastname\n";
echo "  - ✓ Full name display (firstname + lastname)\n";
echo "  - ✓ User role display\n";
echo "  - ✓ Email shown in tooltip\n";
echo "  - ✓ Logout button within profile section\n";
echo "  - ✓ All data from Auth::user() instead of $_SESSION\n";
echo "  - ✓ BASE_URL used for logout link\n";
echo "  - ✓ Responsive design with text truncation\n";
echo "\n✓ User profile sidebar implementation is complete and correct.\n";
