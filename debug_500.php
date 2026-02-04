<?php
/**
 * Debug 500 Error Script
 * Diagnostic tool to identify which core file is causing issues
 * Loads core files one by one and reports progress
 */

// Set error reporting immediately (before anything else)
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== DEBUG 500 - Core Files Loading Test ===\n\n";

// Test 1: Load config/config.php
echo "Attempting to load config/config.php...\n";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ Config geladen... OK\n\n";
} catch (Exception $e) {
    echo "✗ FEHLER beim Laden von config/config.php: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "✗ FEHLER beim Laden von config/config.php: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 2: Load includes/helpers.php
echo "Attempting to load includes/helpers.php...\n";
try {
    require_once __DIR__ . '/includes/helpers.php';
    echo "✓ Helpers geladen... OK\n\n";
} catch (Exception $e) {
    echo "✗ FEHLER beim Laden von includes/helpers.php: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "✗ FEHLER beim Laden von includes/helpers.php: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 3: Load src/Auth.php
echo "Attempting to load src/Auth.php...\n";
try {
    require_once __DIR__ . '/src/Auth.php';
    echo "✓ Auth geladen... OK\n\n";
} catch (Exception $e) {
    echo "✗ FEHLER beim Laden von src/Auth.php: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "✗ FEHLER beim Laden von src/Auth.php: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=== ALLE DATEIEN ERFOLGREICH GELADEN ===\n";
echo "Alle Core-Dateien wurden erfolgreich geladen. Der Fehler liegt woanders.\n";
