# Email-Änderung Bestätigungssystem

## Übersicht

Das E-Mail-Änderungs-Bestätigungssystem ermöglicht es Benutzern, ihre E-Mail-Adresse sicher zu ändern. Der Prozess verwendet einen Token-basierten Bestätigungsmechanismus, um sicherzustellen, dass der Benutzer Zugriff auf die neue E-Mail-Adresse hat.

## Implementierte Komponenten

### 1. Datenbank-Schema
Die Tabelle `email_change_requests` in der Benutzerdatenbank speichert:
- `user_id` - ID des Benutzers
- `new_email` - Die neue E-Mail-Adresse
- `token` - 64-Zeichen Hex-Token
- `expires_at` - Ablaufzeitpunkt (24 Stunden)
- `created_at` - Erstellungszeitpunkt

### 2. User Model (`includes/models/User.php`)
Zwei Hauptmethoden:

#### `createEmailChangeRequest($userId, $newEmail)`
- Validiert E-Mail-Format
- Prüft auf bereits verwendete E-Mail
- Generiert sicheren Token (random_bytes)
- Speichert Anfrage in Datenbank
- Löscht vorherige Anfragen desselben Benutzers
- Gibt Token zurück

#### `confirmEmailChange($token)`
- Sucht Token in Datenbank
- Prüft Ablaufzeit
- Prüft E-Mail-Verfügbarkeit erneut
- Aktualisiert E-Mail in users-Tabelle
- Löscht verwendeten Token
- Gibt true bei Erfolg zurück

### 3. MailService (`src/MailService.php`)

#### `sendEmailChangeConfirmation($toEmail, $token)`
- Erstellt professionelle E-Mail mit IBC-Design
- Enthält Bestätigungslink mit Token
- Verwendet PHPMailer (wenn verfügbar)
- Gibt false zurück, wenn PHPMailer fehlt

### 4. Settings-Seite (`pages/auth/settings.php`)
Behandelt E-Mail-Änderungsanfragen:
- Nimmt neue E-Mail entgegen
- Erstellt Token via `User::createEmailChangeRequest()`
- Sendet Bestätigungs-E-Mail via `MailService`
- Zeigt Erfolgs-/Fehlermeldungen
- Behandelt Session-Nachrichten von API

### 5. Confirmation API (`api/confirm_email.php`)
Behandelt Token-Validierung:
- Nimmt Token als GET-Parameter entgegen
- Ruft `User::confirmEmailChange()` auf
- Aktualisiert Session bei Erfolg
- Leitet zur Settings-Seite weiter
- Setzt Session-Nachrichten

## Vollständiger Ablauf

```
1. Benutzer öffnet Settings-Seite
   └─> pages/auth/settings.php

2. Benutzer ändert E-Mail und sendet Formular
   └─> POST zu pages/auth/settings.php
       ├─> User::createEmailChangeRequest()
       │   ├─> Validiert E-Mail
       │   ├─> Prüft Eindeutigkeit
       │   ├─> Generiert Token
       │   └─> Speichert in DB
       └─> MailService::sendEmailChangeConfirmation()
           └─> Sendet E-Mail mit Link

3. Benutzer erhält E-Mail
   └─> Klickt auf Bestätigungslink
       └─> GET zu api/confirm_email.php?token=...

4. API validiert Token
   └─> User::confirmEmailChange()
       ├─> Prüft Token-Gültigkeit
       ├─> Prüft Ablaufzeit
       ├─> Prüft E-Mail-Verfügbarkeit
       ├─> Aktualisiert users.email
       └─> Löscht Token

5. Session-Update und Redirect
   └─> Aktualisiert $_SESSION['user_email']
       └─> Leitet zu settings.php weiter
           └─> Zeigt Erfolgsmeldung
```

## Sicherheitsfeatures

### Token-Sicherheit
- **Länge**: 64 Zeichen (32 Bytes als Hex)
- **Generierung**: `random_bytes(32)` - kryptographisch sicher
- **Eindeutigkeit**: Einzigartig durch Index in DB
- **Einmalverwendung**: Token wird nach Verwendung gelöscht

### Ablaufschutz
- **Gültigkeitsdauer**: 24 Stunden
- **Prüfung**: Bei jeder Token-Validierung
- **Automatische Bereinigung**: Alte Anfragen werden überschrieben

### E-Mail-Validierung
- **Format**: `filter_var()` mit `FILTER_VALIDATE_EMAIL`
- **Eindeutigkeit**: Doppelte Prüfung (bei Erstellung und Bestätigung)
- **SQL-Injection-Schutz**: Prepared Statements

### Session-Sicherheit
- **Update**: Session-E-Mail wird nach Änderung aktualisiert
- **Isolation**: Nachrichten werden nach Anzeige gelöscht

### Fehlerbehandlung
- **Generische Meldungen**: Keine internen Details für Benutzer
- **Logging**: Detaillierte Fehler im error_log
- **Fallback**: System funktioniert auch ohne E-Mail-Versand

## PHPMailer-Status

Das System funktioniert mit und ohne PHPMailer:

### Mit PHPMailer
```bash
composer install
```
- E-Mails werden versendet
- Benutzer erhält Bestätigungslink per E-Mail

### Ohne PHPMailer
- Token wird trotzdem erstellt
- Link wird im error_log protokolliert
- Benutzer erhält generische Fehlermeldung
- Administrator kann Link aus Logs extrahieren

## Konfiguration

### Erforderliche Konstanten (config/config.php)
```php
define('BASE_URL', 'https://example.com');  // Für Link-Generierung
define('SMTP_HOST', '...');                  // Für E-Mail-Versand
define('SMTP_USER', '...');
define('SMTP_PASS', '...');
```

### Datenbanktabelle
Die Tabelle `email_change_requests` muss existieren:
```sql
CREATE TABLE IF NOT EXISTS email_change_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    new_email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Testing

### Manuelle Tests
1. Login als normaler Benutzer
2. Navigiere zu Settings-Seite
3. Ändere E-Mail-Adresse
4. Prüfe E-Mail-Postfach (oder error_log)
5. Klicke auf Bestätigungslink
6. Verifiziere E-Mail-Änderung

### Edge Cases
- ✓ Ungültiges E-Mail-Format
- ✓ Bereits verwendete E-Mail
- ✓ Abgelaufener Token
- ✓ Ungültiger Token
- ✓ E-Mail-Versand fehlgeschlagen
- ✓ Mehrfache Änderungsanfragen (alte wird überschrieben)

## Wartung

### Log-Überwachung
```bash
tail -f /path/to/php-error.log | grep "Email"
```

### Token-Bereinigung
Alte, nicht verwendete Tokens werden automatisch beim nächsten Änderungsversuch desselben Benutzers gelöscht. Optional kann ein Cron-Job eingerichtet werden:

```sql
DELETE FROM email_change_requests 
WHERE expires_at < NOW();
```

## Fehlerbehebung

### Problem: E-Mail wird nicht versendet
1. Prüfe `composer install` wurde ausgeführt
2. Prüfe SMTP-Konfiguration in .env
3. Prüfe error_log für Details
4. Teste mit `src/MailService::sendTestMail()`

### Problem: Token ungültig
1. Prüfe Ablaufzeit (24h)
2. Prüfe ob Token bereits verwendet wurde
3. Prüfe Datenbank-Verbindung

### Problem: E-Mail-Änderung schlägt fehl
1. Prüfe ob neue E-Mail bereits vergeben
2. Prüfe Datenbankberechtigungen
3. Prüfe error_log für SQL-Fehler

## Best Practices

1. **Regelmäßige Backups** der `email_change_requests` Tabelle
2. **Monitoring** der erfolgreichen/fehlgeschlagenen Änderungen
3. **Rate Limiting** erwägen (z.B. max. 3 Anfragen pro Stunde)
4. **E-Mail-Benachrichtigung** an alte Adresse erwägen
5. **Audit-Log** für E-Mail-Änderungen erwägen

## Erweiterungsmöglichkeiten

- Benachrichtigung an alte E-Mail-Adresse
- Rate Limiting für Missbrauchsschutz
- Admin-Dashboard für Token-Verwaltung
- Automatische Token-Bereinigung via Cron
- Zwei-Faktor-Authentifizierung vor E-Mail-Änderung
- E-Mail-History in separater Tabelle
