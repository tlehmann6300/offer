<?php
/**
 * Test for event_signup_simple.php functionality
 * This tests the code structure and syntax
 */

echo "Testing event_signup_simple.php implementation...\n";
echo "================================================\n\n";

// Test 1: Check file exists
$file = __DIR__ . '/api/event_signup_simple.php';
if (file_exists($file)) {
    echo "✓ File exists: api/event_signup_simple.php\n";
} else {
    echo "✗ File NOT FOUND: api/event_signup_simple.php\n";
    exit(1);
}

// Test 2: Check syntax
$output = [];
$return_code = 0;
exec("php -l $file", $output, $return_code);
if ($return_code === 0) {
    echo "✓ PHP syntax check passed\n";
} else {
    echo "✗ PHP syntax error:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

// Test 3: Check required includes
$content = file_get_contents($file);
$required_includes = [
    'config/config.php',
    'src/Auth.php',
    'src/MailService.php',
    'src/Database.php'
];

foreach ($required_includes as $include) {
    if (strpos($content, $include) !== false) {
        echo "✓ Includes $include\n";
    } else {
        echo "✗ Missing include: $include\n";
    }
}

// Test 4: Check Auth::check() is used
if (strpos($content, 'Auth::check()') !== false) {
    echo "✓ Uses Auth::check() for authentication\n";
} else {
    echo "✗ Missing Auth::check() call\n";
}

// Test 5: Check JSON input reading
if (strpos($content, "file_get_contents('php://input')") !== false) {
    echo "✓ Reads JSON input from php://input\n";
} else {
    echo "✗ Missing JSON input reading\n";
}

// Test 6: Check event_registrations table usage
if (strpos($content, 'event_registrations') !== false) {
    echo "✓ Uses event_registrations table\n";
} else {
    echo "✗ Missing event_registrations table usage\n";
}

// Test 7: Check sendEventConfirmation call
if (strpos($content, 'MailService::sendEventConfirmation') !== false) {
    echo "✓ Calls MailService::sendEventConfirmation\n";
} else {
    echo "✗ Missing MailService::sendEventConfirmation call\n";
}

// Test 8: Check success message
if (strpos($content, 'Erfolgreich angemeldet') !== false) {
    echo "✓ Returns correct success message\n";
} else {
    echo "✗ Missing success message 'Erfolgreich angemeldet'\n";
}

// Test 9: Check try-catch
if (strpos($content, 'try {') !== false && strpos($content, '} catch') !== false) {
    echo "✓ Uses try-catch for error handling\n";
} else {
    echo "✗ Missing try-catch error handling\n";
}

echo "\n================================================\n";
echo "Testing MailService::sendEventConfirmation...\n";
echo "================================================\n\n";

// Test 10: Check MailService has sendEventConfirmation
$mailServiceFile = __DIR__ . '/src/MailService.php';
if (file_exists($mailServiceFile)) {
    echo "✓ MailService.php exists\n";
    
    $mailContent = file_get_contents($mailServiceFile);
    if (strpos($mailContent, 'public static function sendEventConfirmation') !== false) {
        echo "✓ sendEventConfirmation method exists\n";
    } else {
        echo "✗ sendEventConfirmation method NOT FOUND\n";
    }
} else {
    echo "✗ MailService.php NOT FOUND\n";
}

echo "\n================================================\n";
echo "Testing migration files...\n";
echo "================================================\n\n";

// Test 11: Check migration file exists
$migrationFile = __DIR__ . '/sql/migrations/add_event_registrations_table.sql';
if (file_exists($migrationFile)) {
    echo "✓ Migration file exists\n";
    
    $migrationContent = file_get_contents($migrationFile);
    if (strpos($migrationContent, 'CREATE TABLE IF NOT EXISTS event_registrations') !== false) {
        echo "✓ Migration creates event_registrations table\n";
    } else {
        echo "✗ Migration does not create event_registrations table\n";
    }
} else {
    echo "✗ Migration file NOT FOUND\n";
}

// Test 12: Check migration runner exists
$migrationRunner = __DIR__ . '/apply_event_registrations_migration.php';
if (file_exists($migrationRunner)) {
    echo "✓ Migration runner exists\n";
} else {
    echo "✗ Migration runner NOT FOUND\n";
}

echo "\n================================================\n";
echo "All structure tests completed!\n";
echo "================================================\n";
