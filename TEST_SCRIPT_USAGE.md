# Test Script Usage Guide

## Übersicht

Das `test_critical_flows.php` Skript wurde erstellt, um kritische System-Flows automatisiert zu testen.

## Ausführung

### Voraussetzungen
- PHP CLI (Command Line Interface)
- Zugriff auf die Projektdateien
- Konfigurierte Datenbank-Verbindungen (.env-Datei)

### Ausführen des Skripts

```bash
# Im Projekt-Root-Verzeichnis
php test_critical_flows.php
```

### Erwartete Ausgabe

Das Skript führt folgende Tests durch:

1. **Database Connections** (3 Tests)
   - User Database
   - Content Database  
   - Invoice/Rech Database

2. **Critical Tables Existence** (3 Tests)
   - `users` Tabelle in User DB
   - `invoices` Tabelle in Invoice DB
   - `event_documentation` Tabelle in Content DB

3. **Directory Write Permissions** (2 Tests)
   - `uploads/` Verzeichnis
   - `logs/` Verzeichnis

4. **Code Validation - Login Lockout** (1 Test)
   - Prüfung ob `locked_until` korrekt validiert wird

5. **Code Validation - Bulk Operations** (2 Tests)
   - `api/send_invitation.php` (Einzeleinladungen)
   - `api/import_invitations.php` (Bulk-Import)

### Ausgabe-Format

- ✅ **Grün**: Test bestanden
- ❌ **Rot**: Test fehlgeschlagen
- ⚠️ **Gelb**: Warnung

### Exit Codes

- `0`: Alle Tests bestanden
- `1`: Mindestens ein Test fehlgeschlagen

### Beispiel-Ausgabe

```
======================================================================
TEST 1: Database Connections
======================================================================
  [✓] User Database connection successful
  [✓] Content Database connection successful
  [✓] Invoice/Rech Database connection successful

======================================================================
TEST 2: Critical Tables Existence
======================================================================
  [✓] Table "users" exists with all required columns
  [✓] Table "invoices" exists with all required columns
  [✓] Table "event_documentation" exists with all required columns

======================================================================
TEST 3: Directory Write Permissions
======================================================================
  [✓] Directory "uploads/" exists and is writable
  [✓] Directory "logs/" exists and is writable

======================================================================
TEST 4: Code Validation - Login Lockout Check
======================================================================
  [✓] Login lockout check (locked_until) is properly implemented in Auth.php

======================================================================
TEST 5: Code Validation - Bulk Invitation Timeout Prevention
======================================================================
  [!] api/send_invitation.php does not contain loops - single invitation only
  [✓] No bulk operation detected in send_invitation.php - N/A
  [✓] api/import_invitations.php has set_time_limit(0) for bulk operations

======================================================================
TEST SUMMARY
======================================================================

  Total Tests:   11
  Passed:        11
  Warnings:      1

  ✓ All tests passed! (100%)
```

## Fehlerbehebung

### Datenbank-Verbindungsfehler

Wenn Datenbank-Tests fehlschlagen:

1. Überprüfen Sie die `.env` Datei
2. Stellen Sie sicher, dass die Datenbank-Credentials korrekt sind
3. Prüfen Sie ob die Datenbank-Server erreichbar sind

### Schreibrechte-Fehler

Wenn Verzeichnis-Tests fehlschlagen:

```bash
# Schreibrechte setzen
chmod 755 uploads/
chmod 755 logs/
```

### Code-Validierung-Fehler

Wenn Code-Validierungs-Tests fehlschlagen, wurde möglicherweise der Code geändert. Überprüfen Sie:

- `src/Auth.php` für Login-Lockout-Logik
- `api/import_invitations.php` für `set_time_limit(0)`

## Integration in CI/CD

Das Skript kann in CI/CD-Pipelines integriert werden:

```yaml
# Beispiel für GitHub Actions
- name: Run Critical Flows Tests
  run: php test_critical_flows.php
```

## Wartung

Das Skript sollte aktualisiert werden, wenn:

- Neue kritische Tabellen hinzugefügt werden
- Neue kritische Verzeichnisse erforderlich sind
- Neue Sicherheits-Features implementiert werden
- Neue Bulk-Operationen hinzugefügt werden

## Weitere Informationen

Siehe auch:
- `CRITICAL_FLOWS_CHECKLIST.md` - Vollständige Dokumentation der kritischen Flows
- `config/config.php` - Datenbank-Konfiguration
- `includes/database.php` - Datenbank-Verbindungen
