#!/usr/bin/env php
<?php
/**
 * Validation script for tfa_secret column addition
 * This script validates that the schema changes are correct
 */

echo "\n";
echo "=================================================================\n";
echo "TFA_SECRET COLUMN VALIDATION\n";
echo "=================================================================\n\n";

$errors = [];
$warnings = [];

// Check 1: Verify User.php enable2FA method
echo "1. Checking User.php enable2FA method...\n";
$userPhpPath = __DIR__ . '/includes/models/User.php';
if (file_exists($userPhpPath)) {
    $content = file_get_contents($userPhpPath);
    if (strpos($content, 'tfa_secret = ?') !== false) {
        echo "   ✓ enable2FA method references tfa_secret column\n";
    } else {
        $errors[] = "enable2FA method does not reference tfa_secret column";
        echo "   ✗ enable2FA method does not reference tfa_secret column\n";
    }
} else {
    $errors[] = "User.php not found";
    echo "   ✗ User.php not found\n";
}

// Check 2: Verify User.php disable2FA method
echo "\n2. Checking User.php disable2FA method...\n";
if (file_exists($userPhpPath)) {
    $content = file_get_contents($userPhpPath);
    if (strpos($content, 'tfa_secret = NULL') !== false) {
        echo "   ✓ disable2FA method references tfa_secret column\n";
    } else {
        $errors[] = "disable2FA method does not reference tfa_secret column";
        echo "   ✗ disable2FA method does not reference tfa_secret column\n";
    }
}

// Check 3: Verify schema file contains tfa_secret
echo "\n3. Checking schema file (dbs15253086.sql)...\n";
$schemaPath = __DIR__ . '/sql/dbs15253086.sql';
if (file_exists($schemaPath)) {
    $content = file_get_contents($schemaPath);
    if (strpos($content, 'tfa_secret VARCHAR(255)') !== false) {
        echo "   ✓ Schema file includes tfa_secret column definition\n";
    } else {
        $errors[] = "Schema file does not include tfa_secret column";
        echo "   ✗ Schema file does not include tfa_secret column\n";
    }
    
    // Check if it's in the right position (after tfa_enabled)
    if (preg_match('/tfa_enabled.*\n.*tfa_secret/s', $content)) {
        echo "   ✓ tfa_secret column is positioned after tfa_enabled\n";
    } else {
        $warnings[] = "tfa_secret column may not be in the expected position";
        echo "   ⚠ tfa_secret column may not be in the expected position\n";
    }
} else {
    $errors[] = "Schema file not found";
    echo "   ✗ Schema file not found\n";
}

// Check 4: Verify migration file exists
echo "\n4. Checking migration file...\n";
$migrationPath = __DIR__ . '/sql/add_tfa_secret_column.sql';
if (file_exists($migrationPath)) {
    echo "   ✓ Migration file exists\n";
    $content = file_get_contents($migrationPath);
    if (strpos($content, 'ALTER TABLE users') !== false) {
        echo "   ✓ Migration file contains ALTER TABLE statement\n";
    } else {
        $errors[] = "Migration file does not contain ALTER TABLE statement";
        echo "   ✗ Migration file does not contain ALTER TABLE statement\n";
    }
} else {
    $errors[] = "Migration file not found";
    echo "   ✗ Migration file not found\n";
}

// Check 5: Verify README exists
echo "\n5. Checking documentation...\n";
$readmePath = __DIR__ . '/sql/README_add_tfa_secret_column.md';
if (file_exists($readmePath)) {
    echo "   ✓ README documentation exists\n";
} else {
    $warnings[] = "README documentation not found";
    echo "   ⚠ README documentation not found\n";
}

// Check 6: Verify all code references to tfa_secret
echo "\n6. Checking code references to tfa_secret...\n";
$codeFiles = [
    'includes/models/User.php',
    'pages/auth/login.php',
    'src/Auth.php',
    'includes/handlers/AuthHandler.php'
];

$referencesFound = 0;
foreach ($codeFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        if (strpos($content, 'tfa_secret') !== false) {
            $referencesFound++;
        }
    }
}

echo "   ✓ Found tfa_secret references in $referencesFound code files\n";

// Summary
echo "\n=================================================================\n";
echo "VALIDATION SUMMARY\n";
echo "=================================================================\n\n";

if (count($errors) > 0) {
    echo "✗ VALIDATION FAILED\n\n";
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "✓ VALIDATION PASSED\n\n";
    
    if (count($warnings) > 0) {
        echo "Warnings:\n";
        foreach ($warnings as $warning) {
            echo "  - $warning\n";
        }
        echo "\n";
    }
    
    echo "All checks completed successfully!\n";
    echo "The schema changes are ready to be applied to the database.\n\n";
    echo "To apply the migration, run:\n";
    echo "  mysql -u username -p dbs15253086 < sql/add_tfa_secret_column.sql\n\n";
    exit(0);
}
