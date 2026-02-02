# IBC Intranet - Deployment Guide

## Schnellstart für IONOS-Hosting

### 1. Dateien vorbereiten
Laden Sie alle Dateien auf Ihren IONOS-Webspace hoch, typischerweise in das Verzeichnis `/html` oder `/public_html`.

### 2. Datenbanken einrichten

#### Option A: Manuelle Einrichtung über phpMyAdmin

1. Melden Sie sich bei IONOS MySQL Admin (phpMyAdmin) an
2. Wählen Sie die User-Datenbank `dbs15253086` aus
3. Importieren Sie die Datei `sql/user_database_schema.sql`
4. Wählen Sie die Content-Datenbank `dbs15161271` aus
5. Importieren Sie die Datei `sql/content_database_schema.sql`

#### Option B: Via SSH (falls verfügbar)

```bash
# Upload der Dateien via FTP/SFTP
# Dann per SSH verbinden und ausführen:
cd /pfad/zu/ihrer/installation
chmod +x setup.sh
./setup.sh
```

### 3. Verzeichnis-Berechtigungen

Stellen Sie sicher, dass das Upload-Verzeichnis beschreibbar ist:
```bash
chmod 755 assets/uploads
```

Oder via FTP: Rechtsklick → Eigenschaften → 755

### 4. Umgebungsvariablen konfigurieren (Empfohlen)

Für erhöhte Sicherheit in Produktion, setzen Sie diese Variablen:

```bash
export DB_USER_HOST="db5019508945.hosting-data.io"
export DB_USER_NAME="dbs15253086"
export DB_USER_USER="dbu4494103"
export DB_USER_PASS="IHR_PASSWORT"
export DB_CONTENT_HOST="db5019375140.hosting-data.io"
export DB_CONTENT_NAME="dbs15161271"
export DB_CONTENT_USER="dbu2067984"
export DB_CONTENT_PASS="IHR_PASSWORT"
export ENVIRONMENT="production"
```

### 5. Initialen Admin-Benutzer erstellen

Führen Sie das Setup-Skript aus:
```bash
php create_admin.php
```

Folgen Sie den Anweisungen und geben Sie E-Mail und Passwort ein.

**WICHTIG:** Löschen Sie `create_admin.php` nach dem Erstellen!

### 6. System bereinigen (Sicherheit)

**WICHTIG:** Nach erfolgreicher Installation und Admin-Erstellung sollten alle Installations-Skripte entfernt werden:

```bash
php cleanup_system.php
```

Dieses Skript:
- ✅ Löscht `create_tom.php` (falls vorhanden)
- ✅ Löscht `setup.sh` (Installations-Skript)
- ✅ Löscht `import_database.sh` (falls vorhanden)
- ✅ Prüft migrations-Ordner auf sensible Daten und löscht ihn bei Bedarf
- ✅ Löscht sich selbst nach Ausführung

**Ausgabe:** "System bereinigt. Admin-Skripte gelöscht."

Dies verhindert, dass unbefugte Personen das System neu installieren oder zusätzliche Admins anlegen können.

### 7. Erster Login

1. Öffnen Sie Ihre Website in einem Browser
2. Sie werden automatisch zur Login-Seite weitergeleitet
3. Melden Sie sich mit dem Admin-Konto an, das Sie gerade erstellt haben

### 8. Nach dem ersten Login

1. **2FA aktivieren (empfohlen):**
   - Gehen Sie zu: Profil → 2-Faktor-Authentifizierung
   - Klicken Sie auf "2FA aktivieren"
   - Scannen Sie den QR-Code mit Google Authenticator
   - Geben Sie den 6-stelligen Code ein

2. **Erste Benutzer einladen:**
   - Gehen Sie zu: Admin → Benutzerverwaltung
   - Geben Sie E-Mail und Rolle des neuen Benutzers ein
   - Kopieren Sie den Einladungslink und senden Sie ihn an den Benutzer

### 9. Inventar einrichten

1. **Kategorien anpassen:**
   - Standard-Kategorien sind bereits vorhanden
   - Weitere können in der Datenbank hinzugefügt werden

2. **Standorte anpassen:**
   - Standard-Standorte sind bereits vorhanden
   - Weitere können in der Datenbank hinzugefügt werden

3. **Ersten Artikel hinzufügen:**
   - Gehen Sie zu: Inventar → Neuer Artikel
   - Füllen Sie die Felder aus
   - Optional: Bild hochladen

## Konfiguration

### Anpassungen in config/config.php

```php
// Domain anpassen
define('BASE_URL', 'https://ihre-domain.de');

// Session-Dauer (in Sekunden)
define('SESSION_LIFETIME', 3600); // 1 Stunde

// Max. Login-Versuche vor Sperrung
define('MAX_LOGIN_ATTEMPTS', 5);

// Sperrzeit nach zu vielen Fehlversuchen (in Sekunden)
define('LOGIN_LOCKOUT_TIME', 900); // 15 Minuten

// Max. Upload-Größe für Bilder (in Bytes)
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
```

### PHP-Version

Stellen Sie sicher, dass PHP 8.0 oder höher verwendet wird:
- In IONOS: Hosting → PHP-Einstellungen → PHP 8.0 oder höher auswählen

### Erforderliche PHP-Extensions

Die folgenden Extensions sollten aktiviert sein (meist standardmäßig):
- `pdo_mysql` - Für Datenbank-Verbindungen
- `gd` oder `imagick` - Für Bildverarbeitung
- `mbstring` - Für Zeichenkodierung
- `openssl` - Für Verschlüsselung

## Sicherheit

### SSL/HTTPS aktivieren

1. In IONOS: SSL-Zertifikat aktivieren
2. In `config/config.php`: 
   ```php
   define('BASE_URL', 'https://ihre-domain.de'); // https!
   ```
3. In Session-Settings: `session.cookie_secure` ist bereits auf 1 gesetzt

### .htaccess (optional, für Apache)

Erstellen Sie eine `.htaccess`-Datei im Hauptverzeichnis:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<FilesMatch "^(config\.php|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# PHP Security
php_flag display_errors Off
php_value upload_max_filesize 5M
php_value post_max_size 5M
```

### Backup-Strategie

1. **Datenbanken sichern:**
   - Exportieren Sie regelmäßig beide Datenbanken über phpMyAdmin
   - Automatische Backups in IONOS aktivieren

2. **Dateien sichern:**
   - Laden Sie regelmäßig den `assets/uploads`-Ordner herunter
   - IONOS bietet automatische Backups an

## Wartung

### Logs überprüfen

1. System-Logs: Admin → Audit-Logs
2. PHP-Fehler: In `/logs/error.log` (falls konfiguriert)

### Benutzer verwalten

1. Rollen ändern: Admin → Benutzerverwaltung
2. Benutzer löschen: Admin → Benutzerverwaltung
3. 2FA für Benutzer zurücksetzen: Direkt in der Datenbank

### Datenbank-Wartung

Gelegentlich Tabellen optimieren:
```sql
OPTIMIZE TABLE users, inventory, inventory_history, system_logs;
```

## Troubleshooting

### Login funktioniert nicht
- Überprüfen Sie die Datenbank-Verbindungsdaten in `config/config.php`
- Prüfen Sie, ob die User-Datenbank korrekt eingerichtet ist
- Schauen Sie in die Audit-Logs nach fehlgeschlagenen Login-Versuchen

### Bilder werden nicht hochgeladen
- Überprüfen Sie die Berechtigungen von `assets/uploads` (755)
- Prüfen Sie `upload_max_filesize` in PHP-Einstellungen
- Maximale Größe: 5MB (definiert in `config/config.php`)

### 2FA funktioniert nicht
- Zeit auf Server und Client müssen synchron sein
- QR-Code muss vollständig gescannt werden
- Code muss innerhalb von 30 Sekunden eingegeben werden

### Session läuft zu schnell ab
- Erhöhen Sie `SESSION_LIFETIME` in `config/config.php`
- Standard: 3600 Sekunden (1 Stunde)

## Support

Bei Fragen wenden Sie sich an:
- Technischen Administrator
- IONOS Support (für Hosting-Fragen)

## Updates

Bei Updates:
1. Backup erstellen (Datenbank + Dateien)
2. Neue Dateien hochladen
3. Neue SQL-Skripte ausführen (falls vorhanden)
4. Cache leeren (Browser + Server)
5. Funktionen testen

---

**Wichtig:** Nach der Installation sollten Sie alle Standard-Passwörter ändern und 2FA für alle Admin-Konten aktivieren!
