<?php
/**
 * System Cleanup Script
 * 
 * This script removes installation and setup files after system deployment
 * to prevent unauthorized system modifications or admin creation.
 * 
 * IMPORTANT: This script will self-destruct after execution.
 */

// List of files to be deleted
$filesToDelete = [
    'create_tom.php',
    'create_admin.php',
    'setup.sh',
    'import_database.sh'
];

$deletedFiles = [];
$notFoundFiles = [];

// Delete each file if it exists
foreach ($filesToDelete as $file) {
    $filePath = __DIR__ . '/' . $file;
    
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $deletedFiles[] = $file;
        } else {
            echo "FEHLER: Konnte $file nicht löschen.\n";
        }
    } else {
        $notFoundFiles[] = $file;
    }
}

// Check migrations folder for sensitive data
$migrationsPath = __DIR__ . '/sql/migrations';
$shouldDeleteMigrations = false;

if (is_dir($migrationsPath)) {
    // Check if migrations contain sensitive data (passwords, credentials)
    $sensitivePatterns = [
        '/password\s*=\s*["\'][^"\']+["\']/i',
        '/INSERT\s+INTO.*password.*VALUES.*["\'][^"\']{8,}["\']/i',
        '/IDENTIFIED\s+BY\s+["\'][^"\']+["\']/i',
        '/CREATE\s+USER.*IDENTIFIED\s+BY/i'
    ];
    
    $migrationsFiles = glob($migrationsPath . '/*.sql');
    
    // Check each migration file for sensitive patterns
    foreach ($migrationsFiles as $migrationFile) {
        // Use file() to read line by line for better memory efficiency
        $handle = fopen($migrationFile, 'r');
        if (!$handle) {
            echo "WARNUNG: Konnte $migrationFile nicht öffnen.\n";
            continue;
        }
        
        while (($line = fgets($handle)) !== false) {
            foreach ($sensitivePatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $shouldDeleteMigrations = true;
                    fclose($handle);
                    break 2; // Break out of while loop and foreach migrations
                }
            }
        }
        fclose($handle);
        
        // If sensitive data was found, no need to check more files
        if ($shouldDeleteMigrations) {
            break;
        }
    }
}

// Delete migrations folder if it contains sensitive data
if ($shouldDeleteMigrations && is_dir($migrationsPath)) {
    // Recursively delete migrations directory
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($migrationsPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        $func = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $func($fileinfo->getRealPath());
    }
    
    if (rmdir($migrationsPath)) {
        echo "Migrations-Ordner gelöscht (enthielt sensible Daten).\n";
    }
}

// Output summary
echo "\n========================================\n";
echo "System bereinigt. Admin-Skripte gelöscht.\n";
echo "========================================\n\n";

if (!empty($deletedFiles)) {
    echo "Gelöschte Dateien:\n";
    foreach ($deletedFiles as $file) {
        echo "  ✓ $file\n";
    }
    echo "\n";
}

if (!empty($notFoundFiles)) {
    echo "Nicht gefundene Dateien (bereits gelöscht):\n";
    foreach ($notFoundFiles as $file) {
        echo "  - $file\n";
    }
    echo "\n";
}

echo "Dieses Skript wird jetzt gelöscht...\n";

// Self-destruct: Delete this script
if (unlink(__FILE__)) {
    // Script successfully deleted, execution will continue briefly
} else {
    echo "\nFEHLER: Konnte cleanup_system.php nicht löschen.\n";
    echo "Bitte löschen Sie diese Datei manuell aus Sicherheitsgründen!\n";
}
