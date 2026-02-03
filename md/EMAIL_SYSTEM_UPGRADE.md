# E-Mail-System Upgrade - IBC Corporate Design

## Übersicht

Das E-Mail-System wurde komplett auf ein professionelles Corporate Design (IBC) umgestellt.

## Änderungen an `src/MailService.php`

### Neue Methode: `getTemplate()`

Eine private Methode, die das HTML-Template mit IBC Corporate Design erstellt:

```php
private static function getTemplate($title, $bodyContent, $callToAction = null)
```

**Design-Spezifikationen:**
- **Hintergrund**: Hellgrau (#f3f4f6)
- **Container**: Weiße Karte mit Schatten, max-width 600px, zentriert
- **Header**: Dunkelblau (#20234A) mit IBC-Logo (eingebettet als CID)
- **Content**: Klare Typografie (Arial/Helvetica), Überschrift in IBC-Grün (#6D9744)
- **Footer**: Grau, mit Impressum-Link und Copyright

**Logo-Einbindung:**
- Das Logo wird als `AddEmbeddedImage` (Content-ID) eingebunden
- Pfad: `assets/img/ibc_logo_original_navbar.png` (oder `.webp`)
- Wird sofort angezeigt (kein "Bilder herunterladen" nötig)

### Aktualisierte Methode: `sendHelperConfirmation()`

Die Methode wurde aktualisiert, um das neue Template zu verwenden:

**Neue Features:**
- Nutzt das IBC Corporate Design Template
- Event-Daten werden als übersichtliche HTML-Tabelle dargestellt:
  - **Event**: Titel des Events
  - **Wann**: Datum und Uhrzeit
  - **Wo**: Standort
  - **Rolle**: Helfer
  - **Kontaktperson**: Name und E-Mail
- Google-Kalender-Link als schöner grüner Button: "In Kalender speichern"
- ICS-Datei wird weiterhin als Anhang mitgesendet

### Neue Methode: `sendInvitation()`

Sendet Einladungs-E-Mails mit Registrierungs-Token:

```php
public static function sendInvitation($email, $token, $role)
```

**Parameter:**
- `$email`: E-Mail-Adresse des Empfängers
- `$token`: Registrierungs-Token für den Einladungslink
- `$role`: Benutzerrolle (z.B. 'helper', 'admin', etc.)

**Inhalt:**
- **Betreff**: "Einladung zum IBC Intranet"
- **Text**: "Du wurdest als [Rolle] eingeladen."
- **Button**: "Jetzt registrieren" (verlinkt zu `/pages/auth/register.php?token=...`)
- Nutzt das IBC Corporate Design Template
- Logo wird als eingebettetes Bild (CID) mitgesendet

### Neue private Methoden

#### `sendEmailWithAttachment()`
Aktualisiert, um eingebettete Logos zu unterstützen:
- Verwendet `multipart/mixed` für Anhänge
- Verwendet `multipart/related` für eingebettete Bilder
- Logo wird als `Content-ID: <ibc_logo>` eingebunden

#### `sendEmailWithEmbeddedImage()`
Neue Methode für E-Mails ohne Anhänge, aber mit eingebettetem Logo:
- Verwendet `multipart/related` für eingebettete Bilder
- Logo wird als `Content-ID: <ibc_logo>` eingebunden

## Dateistruktur

```
assets/img/
├── README.md                           # Anleitung zur Logo-Platzierung
├── create_placeholder_logo.php         # Skript zur Erstellung eines Platzhalter-Logos
└── ibc_logo_original_navbar.png        # IBC Logo (Platzhalter - bitte ersetzen)

samples/
├── helper_confirmation_email.html      # Beispiel: Einsatzbestätigung
├── invitation_email.html               # Beispiel: Einladung
└── full_event_confirmation_email.html  # Beispiel: Vollständige Event-Bestätigung

tests/
├── test_mail_service.php               # Aktualisierte Unit-Tests
├── test_email_notification_integration.php  # Aktualisierte Integrationstests
├── test_invitation_email.php           # Neue Tests für sendInvitation
└── generate_email_samples.php          # Generiert HTML-Beispiele
```

## Tests

Alle Tests wurden aktualisiert und neue Tests hinzugefügt:

```bash
# Unit-Tests für MailService
php tests/test_mail_service.php

# Integrationstests für E-Mail-Benachrichtigungen
php tests/test_email_notification_integration.php

# Tests für die neue sendInvitation-Methode
php tests/test_invitation_email.php

# HTML-Beispiele generieren
php tests/generate_email_samples.php
```

**Alle Tests bestehen:**
- ✓ 11/11 Unit-Tests
- ✓ 20/20 Integrationstests
- ✓ 23/23 Einladungs-Tests

## E-Mail-Vorschau

HTML-Beispiele wurden im `samples/` Verzeichnis erstellt:
- `helper_confirmation_email.html` - Einsatzbestätigung mit Schicht-Details
- `invitation_email.html` - Einladung zum IBC Intranet
- `full_event_confirmation_email.html` - Einsatzbestätigung für vollständiges Event

Diese können in einem Browser geöffnet werden, um das Design zu prüfen.

## IBC Logo

**Wichtig:** Das aktuelle Logo ist ein Platzhalter. Bitte ersetzen Sie es durch das offizielle IBC Logo:

1. Platzieren Sie das Logo in: `assets/img/ibc_logo_original_navbar.png` (oder `.webp`)
2. Das Logo sollte weiß/transparent sein für die Verwendung auf dem dunkelblauen Hintergrund (#20234A)
3. Empfohlene Größe: ca. 200px Breite

## Verwendung

### Einsatzbestätigung senden

```php
MailService::sendHelperConfirmation(
    $toEmail,
    $toName,
    $event,      // Array mit Event-Daten
    $slot,       // Array mit Slot-Daten oder null
    $icsContent, // ICS-Dateiinhalt
    $googleCalendarLink
);
```

### Einladung senden

```php
MailService::sendInvitation(
    'user@example.com',
    'registration-token-123',
    'helper'  // oder 'admin', 'manager', etc.
);
```

## Sicherheit

- Alle Benutzereingaben werden mit `htmlspecialchars()` escaped
- XSS-Schutz ist implementiert und getestet
- Logo wird sicher als eingebettetes Bild gesendet (kein externer Download nötig)

## Kompatibilität

- Die E-Mails funktionieren in allen gängigen E-Mail-Clients
- Das Design ist responsiv und mobil-freundlich
- ICS-Anhänge sind kompatibel mit Outlook, Apple Calendar, Google Calendar, etc.
