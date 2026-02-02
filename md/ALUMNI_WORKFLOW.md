# Alumni-Registrierung und Validierung - Workflow

## Prozessdiagramm

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ALUMNI-REGISTRIERUNGS-WORKFLOW                  │
└─────────────────────────────────────────────────────────────────────┘

┌───────────────┐
│               │
│     ADMIN     │  1. Admin erstellt Einladung
│               │     • E-Mail: alumni@example.com
└───────┬───────┘     • Rolle: Alumni
        │
        │ generiert 64-Zeichen Token
        ↓
┌───────────────────────────────────────┐
│   EINLADUNGSTOKEN                     │
│   a7b3c9d2e4f5... (64 Zeichen)       │
│   Gültig für: 7 Tage                  │
│   Status: Nicht verwendet             │
└───────────────┬───────────────────────┘
                │
                │ Link wird per E-Mail versendet
                ↓
┌───────────────┐
│               │  2. Alumni öffnet Link
│    ALUMNI     │     • Token wird validiert
│               │     • Registrierungsformular wird angezeigt
└───────┬───────┘
        │
        │ Erstellt Passwort und registriert sich
        ↓
┌───────────────────────────────────────┐
│   ALUMNI-BENUTZERKONTO ERSTELLT       │
│                                       │
│   E-Mail: alumni@example.com          │
│   Rolle: alumni                       │
│   is_alumni_validated: FALSE ❌       │
│                                       │
│   Status: Ausstehend                  │
└───────────────┬───────────────────────┘
                │
                │ Alumni kann sich einloggen
                ↓
┌───────────────────────────────────────┐
│   EINGESCHRÄNKTER ZUGRIFF             │
│                                       │
│   ✅ Dashboard ansehen                │
│   ✅ Inventar ansehen (Lesezugriff)  │
│   ❌ Inventar bearbeiten              │
│   ❌ Alumni-Netzwerk-Daten            │
└───────────────┬───────────────────────┘
                │
                │ Vorstand prüft Identität
                ↓
┌───────────────┐
│               │  3. Vorstand validiert
│   VORSTAND    │     • Geht zu Benutzerverwaltung
│               │     • Klickt auf "Ausstehend" Badge
└───────┬───────┘     • Alumni wird freigegeben
        │
        │ setzt is_alumni_validated = TRUE
        ↓
┌───────────────────────────────────────┐
│   ALUMNI-BENUTZERKONTO VALIDIERT      │
│                                       │
│   E-Mail: alumni@example.com          │
│   Rolle: alumni                       │
│   is_alumni_validated: TRUE ✅        │
│                                       │
│   Status: Verifiziert                 │
└───────────────┬───────────────────────┘
                │
                │ Alumni erhält vollen Lesezugriff
                ↓
┌───────────────────────────────────────┐
│   VOLLER ALUMNI-ZUGRIFF               │
│                                       │
│   ✅ Dashboard ansehen                │
│   ✅ Inventar ansehen (Lesezugriff)  │
│   ✅ Alumni-Netzwerk-Daten            │
│   ❌ Inventar bearbeiten (nur lesen)  │
└───────────────────────────────────────┘
```

## UI-Visualisierung

### Benutzerverwaltung (Admin-Sicht)

```
┌─────────────────────────────────────────────────────────────────────┐
│ Benutzerverwaltung                                   [+ Einladung]   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│ Benutzer              Rolle      2FA / Validierung    Aktionen      │
│ ───────────────────────────────────────────────────────────────────  │
│                                                                      │
│ 👤 admin@ibc.de      [Admin ▼]   🛡️ 2FA              🗑️           │
│                                                                      │
│ 👤 alumni@test.de    [Alumni ▼]  🛡️ 2FA              🗑️           │
│                                   🟡 Ausstehend ← Klicken zum        │
│                                      Freigeben                       │
│                                                                      │
│ 👤 oldmember@test.de [Alumni ▼]  🛡️ 2FA              🗑️           │
│                                   ✅ Verifiziert ← Klicken zum       │
│                                      Sperren                         │
└─────────────────────────────────────────────────────────────────────┘
```

### Registrierung (Alumni-Sicht)

```
┌─────────────────────────────────────────────────────────────────────┐
│                           👤                                         │
│                      REGISTRIERUNG                                   │
│                                                                      │
│  Sie wurden eingeladen als:                                         │
│  ╭────────────────────────────────────────────────────────────╮    │
│  │ alumni@example.com                                         │    │
│  │ Rolle: Alumni                                              │    │
│  │                                                            │    │
│  │ ⚠️ Hinweis für Alumni:                                     │    │
│  │ Ihr Profil wird nach der Registrierung vom Vorstand       │    │
│  │ manuell geprüft und freigeschaltet, bevor Sie Zugriff     │    │
│  │ auf interne Alumni-Netzwerkdaten erhalten.                │    │
│  ╰────────────────────────────────────────────────────────────╯    │
│                                                                      │
│  🔒 Passwort                                                         │
│  [.........................]                                         │
│                                                                      │
│  🔒 Passwort bestätigen                                              │
│  [.........................]                                         │
│                                                                      │
│  [        ✓ Konto erstellen        ]                                │
│                                                                      │
│  ← Zurück zum Login                                                 │
└─────────────────────────────────────────────────────────────────────┘
```

## Status-Badges

### Ausstehend (Pending)
```
┌─────────────────┐
│ 🕐 Ausstehend  │  ← Gelber Hintergrund (bg-yellow-100)
└─────────────────┘
```

### Verifiziert (Verified)
```
┌─────────────────┐
│ ✅ Verifiziert │  ← Grüner Hintergrund (bg-green-100)
└─────────────────┘
```

## Berechtigungsmatrix

| Aktion                          | Member | Alumni (nicht validiert) | Alumni (validiert) | Manager | Board | Admin |
|--------------------------------|--------|-------------------------|-------------------|---------|-------|-------|
| Dashboard ansehen              | ✅     | ✅                      | ✅                | ✅      | ✅    | ✅    |
| Inventar ansehen               | ✅     | ✅                      | ✅                | ✅      | ✅    | ✅    |
| Artikel erstellen              | ❌     | ❌                      | ❌                | ✅      | ✅    | ✅    |
| Artikel bearbeiten             | ❌     | ❌                      | ❌                | ✅      | ✅    | ✅    |
| Bestand ändern                 | ❌     | ❌                      | ❌                | ✅      | ✅    | ✅    |
| Alumni-Netzwerk-Daten          | ❌     | ❌                      | ✅                | ❌      | ✅    | ✅    |
| Benutzer verwalten             | ❌     | ❌                      | ❌                | ❌      | ❌    | ✅    |
| Alumni validieren              | ❌     | ❌                      | ❌                | ❌      | ✅    | ✅    |

## Code-Beispiele

### Prüfung ob Alumni validiert ist
```php
if (AuthHandler::isAlumniValidated()) {
    // Zeige Alumni-Netzwerk-Daten
    echo "Willkommen im Alumni-Netzwerk!";
} else {
    // Zeige Warnung
    echo "Ihr Profil wartet auf Freigabe durch den Vorstand.";
}
```

### Permission-Check für Bearbeitung
```php
// Nur Manager und höher können bearbeiten
if (AuthHandler::hasPermission('manager')) {
    echo '<button>Artikel bearbeiten</button>';
}
```

### Alumni-Rolle prüfen
```php
$user = AuthHandler::getCurrentUser();
if ($user['role'] === 'alumni') {
    if ($user['is_alumni_validated']) {
        // Validierter Alumni
    } else {
        // Nicht validierter Alumni
    }
}
```

## Datenbank-Struktur

```sql
-- Users Tabelle
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni'),
    tfa_secret VARCHAR(32) DEFAULT NULL,
    tfa_enabled TINYINT(1) NOT NULL DEFAULT 0,
    is_alumni_validated TINYINT(1) NOT NULL DEFAULT 0,  ← NEU
    -- ...
);
```

## Security Notes

🔒 **Token-Sicherheit**
- Tokens sind 64 Zeichen lang (32 Bytes random_bytes → 64 hex Zeichen)
- Kryptografisch sicher durch PHP's random_bytes()
- Ablaufdatum: 7 Tage nach Erstellung
- Einmalige Verwendung

🔒 **Alumni-Validierung**
- Manuelle Prüfung durch Vorstand erforderlich
- Schützt vor unbefugtem Zugriff auf Alumni-Netzwerk
- Validierung kann jederzeit widerrufen werden
- Alle Aktionen werden im Audit-Log protokolliert

🔒 **Rollen-Hierarchie**
- Hierarchische Permission-Prüfung
- Alumni = Level 1 (gleich wie Member)
- Validierung ist ein zusätzlicher Check für Alumni-spezifische Daten
- Nicht für generelle Berechtigungen verwendet
