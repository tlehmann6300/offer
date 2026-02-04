<?php
/**
 * Debug 500 Error Script
 * Diagnostic tool to identify which core file is causing issues
 * Loads core files one by one and reports progress
 */

// Set error reporting immediately (before anything else)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set content type for proper display in browsers
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

/**
 * Load a file and report progress
 * @param string $filePath The path to the file to load
 * @param string $displayName The name to display in messages
 */
function loadFileWithCheck($filePath, $displayName) {
    echo "Attempting to load {$filePath}...\n";
    try {
        require_once $filePath;
        echo "✓ {$displayName} geladen... OK\n\n";
    } catch (Throwable $e) {
        echo "✗ FEHLER beim Laden von {$filePath}: " . $e->getMessage() . "\n";
        echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}

echo "=== DEBUG 500 - Core Files Loading Test ===\n\n";

// Test 1: Load config/config.php
loadFileWithCheck(__DIR__ . '/config/config.php', 'Config');

// Test 2: Load includes/helpers.php
loadFileWithCheck(__DIR__ . '/includes/helpers.php', 'Helpers');

// Test 3: Load src/Auth.php
loadFileWithCheck(__DIR__ . '/src/Auth.php', 'Auth');

echo "=== ALLE DATEIEN ERFOLGREICH GELADEN ===\n";
echo "Alle Core-Dateien wurden erfolgreich geladen. Der Fehler liegt woanders.\n";
