# Checkliste für kritische System-Flows

Diese Checkliste dokumentiert die wichtigsten kritischen Flows im IBC Intranet System und deren Status.

## 📋 Übersicht

Dieses Dokument wurde erstellt, um die Überprüfung kritischer Systemkomponenten zu dokumentieren. Verwenden Sie das begleitende Skript `test_critical_flows.php` für automatisierte Tests.

---

## 🔌 1. Datenbank-Verbindungen

### User Database (Benutzer & Authentifizierung)
- [x] Verbindung konfiguriert (DB_USER_HOST, DB_USER_NAME, DB_USER_USER, DB_USER_PASS)
- [x] PDO-Verbindung in `Database::getUserDB()` implementiert
- [x] Fehlerbehandlung vorhanden

**Kritische Tabellen:**
- [x] `users` - Benutzerkonten, Authentifizierung
- [x] `invitation_tokens` - Einladungs-Tokens für neue Benutzer

### Content Database (Inhalte & Events)
- [x] Verbindung konfiguriert (DB_CONTENT_HOST, DB_CONTENT_NAME, DB_CONTENT_USER, DB_CONTENT_PASS)
- [x] PDO-Verbindung in `Database::getContentDB()` implementiert
- [x] Fehlerbehandlung vorhanden

**Kritische Tabellen:**
- [x] `event_documentation` - Event-Dokumentation
- [x] `events` - Event-Verwaltung
- [x] `system_logs` - System-Protokollierung

### Invoice Database (Rechnungen)
- [x] Verbindung konfiguriert (DB_RECH_HOST, DB_RECH_NAME, DB_RECH_USER, DB_RECH_PASS)
- [x] PDO-Verbindung in `Database::getRechDB()` implementiert
- [x] Fehlerbehandlung vorhanden

**Kritische Tabellen:**
- [x] `invoices` - Rechnungsverwaltung

---

## 📁 2. Verzeichnis-Berechtigungen

### Uploads-Verzeichnis
- [x] Verzeichnis existiert: `/uploads/`
- [x] Schreibrechte erforderlich für Datei-Uploads
- [x] Test: Kann Testdatei erstellen und löschen

**Verwendung:**
- Hochladen von Rechnungsbelegen
- Event-bezogene Dateien
- Profilbilder

### Logs-Verzeichnis
- [x] Verzeichnis existiert: `/logs/`
- [x] Schreibrechte erforderlich für Log-Dateien
- [x] Test: Kann Testdatei erstellen und löschen
- [x] `.gitkeep` Datei vorhanden (Verzeichnisstruktur in Git erhalten)
- [x] `.gitignore` konfiguriert (Log-Inhalte nicht versioniert)

**Verwendung:**
- Cron-Job Logs (z.B. `easyverein_sync.log`)
- System-Fehlerprotokolle
- Anwendungsprotokolle

---

## 🔒 3. Sicherheits-Features

### Login-Sperre (Brute-Force-Schutz)
- [x] **Implementiert in:** `src/Auth.php`, Zeile 107-109
- [x] **Feld:** `locked_until` in `users` Tabelle
- [x] **Validierung:** 
  ```php
  if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
      return ['success' => false, 'message' => 'Zu viele Versuche. Wartezeit läuft.'];
  }
  ```
- [x] **Funktionsweise:**
  - Bei 5 fehlgeschlagenen Login-Versuchen: 30 Minuten Sperre
  - Bei 8+ fehlgeschlagenen Versuchen: Permanente Sperre (Admin-Eingriff erforderlich)
- [x] **Test:** Automated check in `test_critical_flows.php`

### Zusätzliche Sicherheitsmaßnahmen
- [x] Permanente Account-Sperre (`is_locked_permanently`)
- [x] Fehlversuch-Zähler (`failed_login_attempts`)
- [x] 2FA-Support (`tfa_enabled`, `tfa_secret`)
- [x] Session-Timeout (30 Minuten Inaktivität)
- [x] CSRF-Token-Validierung
- [x] Secure Session-Cookies (HttpOnly, Secure, SameSite=Strict)

---

## ⏱️ 4. Timeout-Prävention bei Bulk-Operationen

### API-Endpunkte mit Bulk-Operationen

#### ✅ api/import_invitations.php
- [x] **Status:** `set_time_limit(0)` implementiert (Zeile 82)
- [x] **Funktion:** Bulk-Import von Einladungen aus JSON-Datei
- [x] **Schleife:** `foreach ($invitations as $index => $invitation)` (Zeile 100)
- [x] **Verwendung:** Import mehrerer Einladungen auf einmal
- [x] **Test:** Automated check in `test_critical_flows.php`

#### ✅ api/send_invitation.php
- [x] **Status:** Keine Bulk-Operation - nur einzelne Einladungen
- [x] **Funktion:** Erstellt einzelne Einladungs-Links
- [x] **Hinweis:** Kein `set_time_limit(0)` erforderlich (keine Schleifen)
- [x] **Test:** Automated check bestätigt keine Schleifen

---

## 🧪 5. Automatisierte Tests

### Test-Skript: test_critical_flows.php
- [x] **Erstellt:** ✓
- [x] **Ausführbar:** CLI-only (php_sapi_name() === 'cli')
- [x] **Tests:**
  1. [x] Datenbank-Verbindungen (3 Datenbanken)
  2. [x] Kritische Tabellen-Existenz
  3. [x] Verzeichnis-Schreibrechte
  4. [x] Login-Sperre Code-Validierung
  5. [x] Bulk-Operation Timeout-Prävention

### Verwendung
```bash
# Skript ausführen
php test_critical_flows.php

# Erwartete Ausgabe:
# - Farbcodierte Testergebnisse (✓/✗)
# - Detaillierte Fehlerberichte
# - Zusammenfassung mit Erfolgsrate
# - Exit Code 0 bei Erfolg, 1 bei Fehlern
```

---

## 📊 Status-Zusammenfassung

| Kategorie | Status | Notizen |
|-----------|--------|---------|
| Datenbank-Verbindungen | ✅ | Alle 3 Datenbanken konfiguriert |
| Kritische Tabellen | ✅ | users, invoices, event_documentation |
| Verzeichnis-Rechte | ✅ | uploads/ und logs/ mit Schreibrechten |
| Login-Sperre | ✅ | locked_until wird korrekt geprüft |
| Timeout-Prävention | ✅ | set_time_limit(0) in Bulk-Import |
| Test-Skript | ✅ | test_critical_flows.php vollständig |

---

## 🔍 Weitere Hinweise

### Wartung
- Logs-Verzeichnis sollte regelmäßig bereinigt werden
- Uploads-Verzeichnis auf Speicherplatz überwachen
- Datenbank-Backups regelmäßig durchführen

### Monitoring
- Fehlgeschlagene Login-Versuche überwachen
- Datenbank-Verbindungsfehler protokollieren
- Bulk-Operation-Performance überwachen

### Dokumentation
- Diese Checkliste bei Systemänderungen aktualisieren
- Test-Skript bei neuen kritischen Features erweitern
- Code-Kommentare bei Sicherheits-Features pflegen

---

## 📝 Änderungsprotokoll

- **2026-02-10:** Initiale Erstellung
  - Test-Skript `test_critical_flows.php` erstellt
  - Logs-Verzeichnis angelegt mit `.gitkeep`
  - `.gitignore` für Logs konfiguriert
  - Alle Checks dokumentiert und validiert
