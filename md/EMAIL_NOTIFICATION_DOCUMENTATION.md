# E-Mail-Benachrichtigung für Events - Dokumentation

## Übersicht

Diese Dokumentation beschreibt die Integration der E-Mail-Benachrichtigung für Event-Helfer und die Navigation im IBC Intranet.

## Aufgabe 1: E-Mail-Benachrichtigung (MailService)

### Methode: `sendHelperConfirmation`

Die Methode `sendHelperConfirmation` in `src/MailService.php` ermöglicht das Versenden von Bestätigungs-E-Mails an Event-Helfer mit angehängter ICS-Datei.

#### Signatur

```php
public static function sendHelperConfirmation(
    string $toEmail,           // E-Mail-Adresse des Empfängers
    string $toName,            // Name des Empfängers
    array $event,              // Event-Daten (Array)
    array|null $slot,          // Slot-Daten (Array oder null für ganzes Event)
    string $icsContent,        // ICS-Dateiinhalt als String
    string $googleCalendarLink // Google Calendar Link
): bool
```

#### Parameter

- **$toEmail**: Die E-Mail-Adresse des Helfers
- **$toName**: Der Name des Helfers für die persönliche Anrede
- **$event**: Array mit Event-Daten:
  - `id`: Event-ID
  - `title`: Event-Titel
  - `description`: Event-Beschreibung (optional)
  - `location`: Veranstaltungsort (optional)
  - `start_time`: Start-Zeit (Format: 'YYYY-MM-DD HH:MM:SS')
  - `end_time`: End-Zeit (Format: 'YYYY-MM-DD HH:MM:SS')
  - `contact_person`: Kontaktperson (optional)
- **$slot**: Array mit Slot-Daten (null für ganzes Event):
  - `id`: Slot-ID
  - `start_time`: Start-Zeit der Schicht
  - `end_time`: End-Zeit der Schicht
- **$icsContent**: ICS-Dateiinhalt als String (RFC 5545 konform)
- **$googleCalendarLink**: Google Calendar Link zum direkten Hinzufügen

#### Rückgabewert

- `true`: E-Mail wurde erfolgreich versendet
- `false`: Fehler beim Versenden (wird in error_log protokolliert)

#### Beispielverwendung

```php
require_once __DIR__ . '/src/MailService.php';

$event = [
    'id' => 123,
    'title' => 'Sommerfest 2024',
    'description' => 'Jährliches Sommerfest mit Musik und Essen',
    'location' => 'Hauptcampus, Gebäude H',
    'start_time' => '2024-07-15 10:00:00',
    'end_time' => '2024-07-15 18:00:00',
    'contact_person' => 'Max Mustermann (max@example.com)'
];

$slot = [
    'id' => 456,
    'start_time' => '2024-07-15 14:00:00',
    'end_time' => '2024-07-15 16:00:00'
];

$icsContent = "BEGIN:VCALENDAR\nVERSION:2.0\n...END:VCALENDAR";
$googleLink = "https://calendar.google.com/calendar/render?action=TEMPLATE&text=...";

$success = MailService::sendHelperConfirmation(
    'helfer@example.com',
    'Anna Schmidt',
    $event,
    $slot,
    $icsContent,
    $googleLink
);

if ($success) {
    echo "E-Mail wurde erfolgreich versendet!";
} else {
    echo "Fehler beim Versenden der E-Mail.";
}
```

#### E-Mail-Inhalt

Die E-Mail wird als HTML-Mail versendet und enthält:

1. **Header**: "Einsatzbestätigung"
2. **Begrüßung**: "Hallo [Name],"
3. **Event-Details**:
   - Event-Titel
   - Schichtzeit (oder gesamte Event-Zeit)
   - Veranstaltungsort (falls angegeben)
   - Kontaktperson (falls angegeben)
   - Beschreibung (falls angegeben)
4. **Aktionen**:
   - Button zum Hinzufügen zu Google Calendar
   - Hinweis auf angehängte ICS-Datei
5. **Footer**: Automatische Generierung durch IBC Intranet

#### Sicherheit

- Alle Benutzereingaben werden mit `htmlspecialchars()` escaped
- XSS-Angriffe werden verhindert
- ICS-Anhang wird base64-kodiert als MIME-Anhang versendet

#### Technische Details

- Content-Type: `multipart/mixed` für E-Mail mit Anhang
- ICS-Anhang: `text/calendar` mit base64-Encoding
- Character Encoding: UTF-8
- SMTP-Konfiguration: Aus `config/config.php`

## Aufgabe 2: Navigation (main_layout.php)

### Events-Navigation

Die Navigation wurde um Event-Links erweitert, die in `includes/templates/main_layout.php` implementiert sind.

#### Für alle Benutzer

**Link**: Events  
**Ziel**: `pages/events/index.php`  
**Beschreibung**: Übersicht aller verfügbaren Events  
**Icon**: `fa-calendar-check`

Dieser Link ist für alle eingeloggten Benutzer sichtbar, unabhängig von ihrer Rolle.

```php
<a href="../events/index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
    <i class="fas fa-calendar-check w-5"></i>
    <span>Events</span>
</a>
```

#### Für privilegierte Benutzer

**Link**: Event-Verwaltung  
**Ziel**: `pages/events/manage.php`  
**Berechtigung**: Nur für Rollen: `admin`, `board`, `manager`, `alumni_board`  
**Beschreibung**: Event-Management und Administration  
**Icon**: `fa-calendar-alt`

Dieser Link ist nur für Benutzer mit den Rollen `board`, `manager`, `alumni_board` oder `admin` sichtbar.

```php
<?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'board', 'alumni_board', 'manager'])): ?>
<a href="../events/manage.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-white/10 transition">
    <i class="fas fa-calendar-alt w-5"></i>
    <span>Event-Verwaltung</span>
</a>
<?php endif; ?>
```

#### Hinweise

- Die Navigation wird automatisch basierend auf der Benutzerrolle in `$_SESSION['user_role']` angepasst
- Die Rolle `admin` wurde als sinnvolle Erweiterung hinzugefügt (zusätzlich zu den geforderten Rollen)
- Beide Seiten (`index.php` und `manage.php`) existieren und sind funktionsfähig

## Tests

### Vorhandene Tests

1. **tests/test_mail_service.php**: Ursprünglicher Test für E-Mail-Body-Generierung
2. **tests/test_email_notification_integration.php**: Umfassender Integrationstest
   - Methodensignatur und Parameter
   - E-Mail-Body mit allen erforderlichen Elementen
   - HTML-Struktur und Styling
   - ICS-Anhang-Implementierung
   - XSS-Schutz
   - Vollständige Event-Zeitangaben
3. **tests/test_navigation_events.php**: Test für Navigation
   - Events-Link für alle Benutzer
   - Event-Verwaltung für privilegierte Rollen
   - Rollenbasierte Zugriffskontrolle
   - Icons und Styling

### Tests ausführen

```bash
# Alle E-Mail-Tests
php tests/test_mail_service.php
php tests/test_email_notification_integration.php

# Navigation-Test
php tests/test_navigation_events.php
```

## Zusammenfassung

✅ **Aufgabe 1 abgeschlossen**: Die Methode `sendHelperConfirmation` ist vollständig implementiert mit:
- Korrekten Parametern
- HTML-E-Mail mit freundlicher Begrüßung
- ICS-Datei als echter E-Mail-Anhang (multipart/mixed, base64)
- XSS-Schutz durch htmlspecialchars
- Unterstützung für Slot-spezifische und volle Event-Zeiten

✅ **Aufgabe 2 abgeschlossen**: Die Navigation ist korrekt implementiert mit:
- "Events"-Link für alle Benutzer
- "Event-Verwaltung"-Link nur für `board`, `manager`, `alumni_board` (+ `admin`)
- Moderne Gestaltung mit Icons und Hover-Effekten
- Rollenbasierte Zugriffskontrolle

Alle Tests bestanden erfolgreich! ✓
