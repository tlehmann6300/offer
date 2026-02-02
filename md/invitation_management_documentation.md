# Einladungs-Management Dokumentation

## Übersicht
Das Einladungs-Management-System ermöglicht es Vorstandsmitgliedern und Administratoren, neue Benutzer einfach und sicher einzuladen, ohne direkt in der Datenbank arbeiten zu müssen.

## Funktionen

### 1. API-Endpunkte

#### `/api/send_invitation.php` (POST)
Erstellt einen neuen Einladungslink.

**Berechtigungen:** board, alumni_board, admin

**Eingabe:**
- `email` (string, required): E-Mail-Adresse des einzuladenden Benutzers
- `role` (string, required): Rolle (member, alumni, manager, alumni_board, board, admin)

**Ausgabe:**
```json
{
  "success": true,
  "link": "https://domain.com/pages/auth/register.php?token=abc123...",
  "email": "user@example.com",
  "role": "member"
}
```

**Validierungen:**
- E-Mail-Format-Prüfung
- Prüfung auf existierenden Benutzer
- Prüfung auf offene Einladung für diese E-Mail
- Rollen-Whitelist-Prüfung

#### `/api/get_invitations.php` (GET)
Listet alle offenen (nicht verwendeten, nicht abgelaufenen) Einladungen auf.

**Berechtigungen:** board, alumni_board, admin

**Ausgabe:**
```json
{
  "success": true,
  "invitations": [
    {
      "id": 1,
      "token": "abc123...",
      "email": "user@example.com",
      "role": "member",
      "created_at": "2024-01-01 12:00:00",
      "expires_at": "2024-01-08 12:00:00",
      "created_by_email": "admin@example.com",
      "link": "https://domain.com/pages/auth/register.php?token=abc123..."
    }
  ]
}
```

#### `/api/delete_invitation.php` (POST)
Löscht eine offene Einladung.

**Berechtigungen:** board, alumni_board, admin

**Eingabe:**
- `invitation_id` (integer, required): ID der zu löschenden Einladung

**Ausgabe:**
```json
{
  "success": true,
  "message": "Einladung erfolgreich gelöscht"
}
```

### 2. UI-Komponente

#### `/templates/components/invitation_management.php`

Moderne Tailwind CSS-basierte Komponente mit folgenden Elementen:

**Einladung erstellen:**
- E-Mail-Eingabefeld
- Rollen-Dropdown
- "Link erstellen"-Button
- Anzeige des generierten Links mit Kopier-Funktion

**Offene Einladungen:**
- Tabelle mit allen offenen Einladungen
- Spalten: E-Mail, Rolle, Erstellt am, Läuft ab, Erstellt von, Link, Aktionen
- Aktualisieren-Button
- Löschen-Button pro Einladung
- Link-Kopieren-Button pro Einladung

### 3. Integration

#### `/pages/admin/users.php`

**Tab-Navigation:**
- "Benutzerliste"-Tab (für alle Admins sichtbar)
- "Einladungen"-Tab (nur für board/alumni_board/admin sichtbar)

**Zugriffssteuerung:**
```php
$canManageInvitations = AuthHandler::hasPermission('board');
```

## Sicherheitsfeatures

1. **Rollenbasierte Zugriffskontrolle:** Nur board-Level und höher
2. **E-Mail-Validierung:** PHP filter_var mit FILTER_VALIDATE_EMAIL
3. **Rollen-Validierung:** Whitelist-Check
4. **Duplikat-Prüfung:** Keine mehrfachen offenen Einladungen für dieselbe E-Mail
5. **Bestehende-Benutzer-Prüfung:** Keine Einladung, wenn Benutzer bereits existiert
6. **Token-Ablauf:** 7 Tage Gültigkeit
7. **SQL-Injection-Schutz:** Prepared Statements
8. **Session-Validierung:** Bei jedem API-Aufruf

## Benutzerführung

### Einladung erstellen:
1. Navigieren zu "Benutzerverwaltung" → Tab "Einladungen"
2. E-Mail-Adresse eingeben
3. Rolle auswählen
4. "Link erstellen" klicken
5. Generierten Link kopieren
6. Link per WhatsApp, E-Mail etc. versenden

### Einladung verwalten:
- **Aktualisieren:** Klick auf "Aktualisieren"-Button
- **Löschen:** Klick auf Papierkorb-Icon bei der entsprechenden Einladung
- **Link kopieren:** Klick auf "Kopieren"-Button in der Tabelle

## Technische Details

### Token-Generierung:
```php
$token = bin2hex(random_bytes(32)); // 64 Zeichen
$expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 Tage
```

### Datenbank-Schema:
```sql
CREATE TABLE invitation_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni'),
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    used_by INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### AJAX-Kommunikation:
- Alle API-Aufrufe erfolgen asynchron über Fetch API
- JSON-Antworten
- Automatische Aktualisierung der UI
- Fehlerbehandlung mit Benutzer-Feedback

## Testing

Testskript verfügbar unter: `/tests/test_invitation_management.php`

```bash
php tests/test_invitation_management.php
```

## Änderungsprotokoll

### Version 1.0 (2024-02-02)
- Initiale Implementierung
- 3 API-Endpunkte (send, get, delete)
- UI-Komponente mit AJAX-Funktionalität
- Integration in Benutzerverwaltung
- Umfassende Sicherheitsprüfungen
