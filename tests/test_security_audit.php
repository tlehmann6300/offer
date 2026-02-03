<?php
/**
 * Test für Security Audit Script
 * Testet die Funktionalität des Security Audit Systems
 */

require_once __DIR__ . '/../security_audit.php';

echo "=== Security Audit Test ===\n\n";

// Test 1: Audit durchführen
echo "Test 1: Security Audit durchführen...\n";
$audit = SecurityAudit::getAuditResults(__DIR__ . '/..');
echo "✓ Audit abgeschlossen\n";
echo "  - Anzahl Warnungen: " . count($audit['warnings']) . "\n";
echo "  - System sicher: " . ($audit['is_secure'] ? 'Ja' : 'Nein') . "\n";
echo "  - Geprüft am: " . $audit['checked_at'] . "\n\n";

// Test 2: Warnungen anzeigen
if (!empty($audit['warnings'])) {
    echo "Test 2: Gefundene Sicherheitswarnungen:\n";
    foreach ($audit['warnings'] as $i => $warning) {
        echo "  " . ($i + 1) . ". [" . strtoupper($warning['severity']) . "] " . $warning['path'] . "\n";
        echo "     " . $warning['message'] . "\n";
    }
    echo "\n";
}

// Test 3: Dashboard-Warnung generieren
echo "Test 3: Dashboard-Warnung HTML generieren...\n";
$dashboardWarning = SecurityAudit::getDashboardWarning(__DIR__ . '/..');
if (!empty($dashboardWarning)) {
    echo "✓ HTML-Warnung generiert (" . strlen($dashboardWarning) . " Zeichen)\n";
    // Prüfe, ob HTML-Tags vorhanden sind
    if (strpos($dashboardWarning, '<div') !== false && 
        strpos($dashboardWarning, 'Sicherheitswarnung') !== false) {
        echo "✓ HTML-Struktur korrekt\n";
    } else {
        echo "✗ HTML-Struktur fehlerhaft\n";
    }
} else {
    echo "✓ Keine Warnung erforderlich (System ist sicher)\n";
}
echo "\n";

// Test 4: Testen mit nicht existierendem Verzeichnis
echo "Test 4: Test mit nicht existierendem Verzeichnis...\n";
$emptyAudit = SecurityAudit::getAuditResults('/tmp/nonexistent');
echo "✓ Audit abgeschlossen ohne Fehler\n";
echo "  - Anzahl Warnungen: " . count($emptyAudit['warnings']) . "\n\n";

// Test 5: JSON-Output testen
echo "Test 5: JSON-Output testen...\n";
$json = json_encode($audit, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✓ JSON-Kodierung erfolgreich\n";
} else {
    echo "✗ JSON-Kodierung fehlgeschlagen: " . json_last_error_msg() . "\n";
}
echo "\n";

// Zusammenfassung
echo "=== Test-Zusammenfassung ===\n";
echo "Alle Tests abgeschlossen.\n";
if (!$audit['is_secure']) {
    echo "⚠️  WICHTIG: Es wurden " . count($audit['warnings']) . " Sicherheitswarnungen gefunden!\n";
    echo "Diese Dateien sollten vom Produktionsserver entfernt werden.\n";
} else {
    echo "✓ Keine Sicherheitswarnungen - System ist sicher!\n";
}
