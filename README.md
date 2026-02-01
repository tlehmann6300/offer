# IBC Intranet System

Ein professionelles Intranet-System für den Verein "IBC" mit Token-basiertem Login, 2-Faktor-Authentifizierung und vollständigem Inventar-Management.

## 🌟 Features

### Authentifizierung & Sicherheit
- ✅ Token-basiertes Einladungssystem (kein O365)
- ✅ Sichere Passwort-Speicherung mit Argon2ID
- ✅ 2-Faktor-Authentifizierung (TOTP/Google Authenticator)
- ✅ Rate-Limiting gegen Brute-Force-Angriffe
- ✅ Sichere Session-Verwaltung
- ✅ Account-Sperrung nach fehlgeschlagenen Login-Versuchen

### Inventar-System
- ✅ CRUD-Operationen für Artikel, Kategorien und Standorte
- ✅ Schnelle Bestandsanpassung (+/-) mit Kommentarpflicht
- ✅ Vollständige Revisionssicherheit (Historie aller Änderungen)
- ✅ Dashboard mit Statistiken
- ✅ Filterfunktion nach Kategorien, Standorten und Suchbegriff
- ✅ Bild-Upload für Artikel
- ✅ Warnungen bei niedrigem Bestand

### Rollen & Berechtigungen
- **Admin/Vorstand**: Vollzugriff, Benutzerverwaltung, Audit-Logs
- **Alumni-Vorstand**: Vollzugriff wie Vorstand, spezielle Alumni-Verwaltung
- **Ressortleiter**: Inventar verwalten, Bestand ändern
- **Mitglied**: Nur Lesezugriff auf Inventar
- **Alumni**: Lesezugriff, benötigt Validierung durch Vorstand für Alumni-Netzwerkdaten

Weitere Details zum Alumni-System: siehe [ALUMNI_SYSTEM.md](ALUMNI_SYSTEM.md)

### Design & UX
- ✅ Moderne Benutzeroberfläche mit Tailwind CSS
- ✅ Mobile-First responsive Design
- ✅ Card-basiertes Layout für Touch-Geräte
- ✅ Glassmorphism-Effekte
- ✅ Intuitive Navigation

## 🗄️ Datenbank-Architektur

Das System verwendet zwei getrennte MySQL-Datenbanken für verbesserte Sicherheit:

### User-Datenbank (dbs15253086)
- `users` - Benutzerkonten, Logins, Passwörter
- `alumni_profiles` - Alumni-Profile
- `invitation_tokens` - Einladungstoken
- `user_sessions` - Session-Management

### Content-Datenbank (dbs15161271)
- `inventory` - Inventarartikel
- `inventory_history` - Änderungshistorie (Audit-Log)
- `categories` - Kategorien
- `locations` - Standorte
- `system_logs` - System-Aktivitäten

## 📋 Installation

### Voraussetzungen
- PHP 8.0 oder höher
- MySQL 5.7 oder höher
- Webserver (Apache/Nginx)
- IONOS Hosting-Account

### Schritt 1: Datenbanken einrichten

1. Führen Sie die SQL-Skripte aus:
   ```bash
   # User Database
   mysql -h <host> -u <user> -p <database> < sql/user_database_schema.sql
   # Content Database
   mysql -h <host> -u <user> -p <database> < sql/content_database_schema.sql
   ```

### Schritt 2: Initialen Admin erstellen

Führen Sie das Setup-Skript aus und löschen Sie es danach:
```bash
php create_admin.php
# Nach erfolgreichem Setup:
rm create_admin.php
```

### Schritt 3: Konfiguration

**Wichtig:** Für Produktion verwenden Sie Umgebungsvariablen statt der Fallback-Werte in `config/config.php`:

```bash
export DB_USER_HOST="your_host"
export DB_USER_PASS="your_password"
export ENVIRONMENT="production"
# ... weitere Variablen
```

### Schritt 3: Konfiguration

**Wichtig:** Für Produktion verwenden Sie Umgebungsvariablen statt der Fallback-Werte in `config/config.php`:

```bash
export DB_USER_HOST="your_host"
export DB_USER_PASS="your_password"
export ENVIRONMENT="production"
# ... weitere Variablen
```

### Schritt 4: Verzeichnis-Berechtigungen

Stellen Sie sicher, dass der Webserver Schreibrechte hat:
```bash
chmod 755 assets/uploads
```

### Schritt 4: Konfiguration anpassen

Bearbeiten Sie `config/config.php` und passen Sie bei Bedarf:
- `BASE_URL` - Ihre Domain
- `SESSION_LIFETIME` - Session-Dauer
- Weitere Einstellungen

## 🚀 Erste Schritte

1. **Login**: Besuchen Sie die Startseite und melden Sie sich mit dem Admin-Account an
2. **Benutzer einladen**: Gehen Sie zu Admin → Benutzerverwaltung und laden Sie neue Benutzer ein
3. **Kategorien & Standorte**: Diese sind bereits vorkonfiguriert, können aber angepasst werden
4. **Artikel hinzufügen**: Gehen Sie zu Inventar → Neuer Artikel

## 📱 Verwendung

### Inventar-Verwaltung

#### Artikel hinzufügen
1. Navigieren Sie zu "Inventar" → "Neuer Artikel"
2. Füllen Sie alle erforderlichen Felder aus
3. Optional: Laden Sie ein Bild hoch
4. Klicken Sie auf "Artikel erstellen"

#### Bestand anpassen
1. Öffnen Sie einen Artikel in der Detailansicht
2. Nutzen Sie die Quick-Buttons (+1, +10, -1, -10) oder geben Sie eine Menge ein
3. Wählen Sie einen Grund (z.B. "Verliehen", "Gekauft")
4. Fügen Sie einen Kommentar hinzu (Pflichtfeld!)
5. Bestätigen Sie die Änderung

### Benutzerverwaltung (nur Admins)

#### Neuen Benutzer einladen
1. Gehen Sie zu Admin → Benutzerverwaltung
2. Geben Sie E-Mail und Rolle ein
3. Klicken Sie auf "Einladung senden"
4. Senden Sie den generierten Link an den Benutzer

#### Rolle ändern
1. In der Benutzerliste die Rolle im Dropdown ändern
2. Die Änderung wird sofort gespeichert

### 2-Faktor-Authentifizierung einrichten

1. Gehen Sie zu Profil
2. Klicken Sie auf "2FA aktivieren"
3. Scannen Sie den QR-Code mit Ihrer Authenticator-App
4. Geben Sie den 6-stelligen Code ein
5. 2FA ist jetzt aktiviert

## 🔒 Sicherheit

### Rate Limiting
- Nach 5 fehlgeschlagenen Login-Versuchen wird das Konto für 15 Minuten gesperrt

### Passwort-Anforderungen
- Mindestens 8 Zeichen
- Argon2ID-Hashing

### Session-Sicherheit
- HTTPOnly und Secure Cookies
- Session-Regenerierung alle 30 Minuten
- Schutz vor Session-Fixation

### Audit-Logs
Alle wichtigen Aktionen werden protokolliert:
- Login/Logout
- Bestandsänderungen
- Benutzer-Aktionen
- System-Events

## 🎨 Anpassung

### Farben ändern
Bearbeiten Sie die CSS-Variablen in `includes/templates/main_layout.php`:
```css
:root {
    --primary: #667eea;
    --secondary: #764ba2;
}
```

### Kategorien hinzufügen
Führen Sie SQL aus:
```sql
INSERT INTO categories (name, description, color) 
VALUES ('Name', 'Beschreibung', '#HEX-Farbe');
```

## 📊 Dashboard

Das Dashboard zeigt:
- Gesamte Artikel
- Gesamtwert des Inventars
- Artikel mit niedrigem Bestand
- Aktivitäten der letzten 7 Tage

## 🔧 Wartung

### Logs überprüfen
Admin-Benutzer können alle System-Logs einsehen:
- Admin → Audit-Logs
- Filterbar nach Aktion, Benutzer und Zeitraum

### Backup
Regelmäßige Backups beider Datenbanken sind empfohlen:
```bash
mysqldump -h HOST -u USER -p DATABASE > backup_$(date +%Y%m%d).sql
```

## 📞 Support

Bei Fragen oder Problemen:
1. Überprüfen Sie die Logs unter Admin → Audit-Logs
2. Kontaktieren Sie den Administrator

## 📝 Lizenz

Dieses System ist proprietär und nur für die Nutzung durch den IBC-Verein bestimmt.

## 🔄 Updates

### Version 1.0.0 (2026-02-01)
- Initiale Veröffentlichung
- Vollständiges Login-System mit 2FA
- Inventar-Management mit Historie
- Benutzerverwaltung
- Audit-Logs
- Mobile-First Design

---

© 2026 IBC Intranet System
