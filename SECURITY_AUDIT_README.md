# Security Audit - Sicherheitsprüfung für Installationsdateien

## Übersicht

Das Security Audit System prüft automatisch, ob sensible Installations- und Wartungsdateien noch auf dem Produktionsserver vorhanden sind. Diese Dateien sollten nach der Installation aus Sicherheitsgründen vom Server gelöscht werden.

## Funktionen

- **Automatische Prüfung** von sensiblen Dateien und Verzeichnissen
- **Warnungen im Admin-Dashboard** für Administratoren
- **Severity-Level** (Hoch/Mittel) für verschiedene Dateitypen
- **JSON-API** für programmatischen Zugriff
- **Standalone-Ausführung** für manuelle Checks

## Geprüfte Dateien

### Hohe Priorität (HIGH)
- `setup_admin.php` - Admin-Setup-Skript
- `create_admin.php` - Admin-Erstellungsskript
- `cleanup_final.php` - Finales Cleanup-Skript
- `cleanup_structure.php` - Struktur-Cleanup-Skript
- `cleanup_system.php` - System-Cleanup-Skript
- `debug_paths.php` - Debug-Pfad-Skript
- `fix_event_db.php` - Event-DB-Fix-Skript
- `verify_db_schema.php` - DB-Schema-Verifikationsskript

### Mittlere Priorität (MEDIUM)
- `sql/migrate_add_event_fields.php` - Event-Felder-Migrationsskript
- `sql/migrations/` - SQL-Migrations-Verzeichnis (falls vorhanden)

## Verwendung

### 1. Automatische Integration im Dashboard

Die Security Audit-Warnung wird automatisch im Admin-Dashboard angezeigt, wenn ein Benutzer mit Admin-Rechten angemeldet ist.

**Implementierung:**
```php
// In pages/dashboard/index.php
if (AuthHandler::hasPermission('admin')) {
    require_once __DIR__ . '/../../security_audit.php';
    $securityWarning = SecurityAudit::getDashboardWarning(__DIR__ . '/../..');
}
```

### 2. Manuelle Ausführung (CLI)

```bash
php security_audit.php
```

Gibt JSON-formatierte Ergebnisse aus:
```json
{
    "warnings": [
        {
            "type": "file",
            "path": "setup_admin.php",
            "description": "Admin-Setup-Skript",
            "severity": "high",
            "message": "Die Datei 'setup_admin.php' (Admin-Setup-Skript) sollte gelöscht werden."
        }
    ],
    "is_secure": false,
    "checked_at": "2026-02-03 11:45:00"
}
```

### 3. Programmatische Verwendung

```php
require_once 'security_audit.php';

// Audit durchführen
$audit = SecurityAudit::getAuditResults();

// Prüfen ob System sicher ist
if ($audit['is_secure']) {
    echo "System ist sicher!";
} else {
    echo "Gefundene Warnungen: " . count($audit['warnings']);
}

// Dashboard-Warnung HTML generieren
$html = SecurityAudit::getDashboardWarning();
echo $html;
```

## API-Methoden

### `SecurityAudit::performAudit($baseDir = null)`
Führt den Security Audit durch und gibt ein Array mit den Ergebnissen zurück.

**Parameter:**
- `$baseDir` (string|null): Das Basisverzeichnis für die Prüfung. Standard: `__DIR__`

**Rückgabe:**
```php
[
    'warnings' => [...],    // Array mit Warnungen
    'is_secure' => bool,    // true wenn keine Warnungen
    'checked_at' => string  // Zeitstempel der Prüfung
]
```

### `SecurityAudit::getDashboardWarning($baseDir = null)`
Gibt eine HTML-formatierte Warnung für das Dashboard zurück.

**Parameter:**
- `$baseDir` (string|null): Das Basisverzeichnis für die Prüfung

**Rückgabe:**
- HTML-String mit der Warnung oder leerer String wenn alles sicher ist

### `SecurityAudit::getAuditResults($baseDir = null)`
Alias für `performAudit()` - für JSON-API-Verwendung.

## Dashboard-Integration

Die Sicherheitswarnung erscheint als prominenter roter Banner am oberen Rand des Dashboards:

- **Nur für Admins sichtbar** (erfordert `admin`-Rolle)
- **Automatische Aktualisierung** bei jedem Dashboard-Aufruf
- **Detaillierte Liste** aller gefundenen Dateien mit Severity-Level
- **Handlungsempfehlungen** zur Behebung

## Tests

Führen Sie den Test aus, um die Funktionalität zu überprüfen:

```bash
php tests/test_security_audit.php
```

Der Test prüft:
- ✓ Audit-Durchführung
- ✓ Warngenerierung
- ✓ HTML-Output
- ✓ JSON-Kodierung
- ✓ Error-Handling

## Deployment-Empfehlungen

### Für Produktion

1. **Vor dem Deployment:** Stellen Sie sicher, dass alle sensiblen Dateien gelöscht wurden
2. **Nach dem Deployment:** Überprüfen Sie das Admin-Dashboard auf Warnungen
3. **Automatisierung:** Integrieren Sie die Prüfung in Ihr Deployment-Skript

### Deployment-Skript Beispiel

```bash
#!/bin/bash
# deployment.sh

# Security Audit vor Deployment
echo "Führe Security Audit durch..."
php security_audit.php > audit_results.json

# Prüfe ob Warnungen vorhanden sind
if grep -q '"is_secure": false' audit_results.json; then
    echo "⚠️  WARNUNG: Sensible Dateien gefunden!"
    cat audit_results.json
    read -p "Trotzdem fortfahren? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Lösche sensible Dateien
rm -f setup_admin.php
rm -f create_admin.php
rm -f cleanup_*.php
rm -f debug_paths.php
rm -f fix_event_db.php
rm -f verify_db_schema.php
rm -f sql/migrate_*.php
rm -rf sql/migrations/

echo "✓ Deployment abgeschlossen"
```

## Wartung

### Neue Dateien hinzufügen

Um weitere sensible Dateien zur Prüfung hinzuzufügen, bearbeiten Sie die statischen Arrays in `security_audit.php`:

```php
private static $sensitiveFiles = [
    'neue_datei.php' => 'Beschreibung der Datei',
    // ...
];
```

### Severity-Level anpassen

Ändern Sie das `severity`-Level in der `performAudit()`-Methode:
- `'high'` - Kritische Sicherheitsrisiken
- `'medium'` - Mittlere Sicherheitsrisiken
- `'low'` - Geringe Sicherheitsrisiken (optional)

## Sicherheitshinweise

⚠️ **WICHTIG:**
- Diese Dateien enthalten oft Passwörter oder sensible Konfigurationsdaten
- Sie ermöglichen unbefugten Zugriff auf Admin-Funktionen
- Sie können Datenbank-Strukturen offenlegen
- Sie sollten **niemals** auf Produktionsservern verbleiben

## Support

Bei Fragen oder Problemen wenden Sie sich an das Entwicklungsteam.

## Changelog

### Version 1.0.0 (2026-02-03)
- Initiale Implementierung
- Dashboard-Integration für Admins
- CLI-Support
- JSON-API
- Automatische Tests
