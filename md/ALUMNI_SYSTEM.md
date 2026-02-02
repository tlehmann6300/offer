# Alumni-System und erweiterte Rollen

## Übersicht

Das IBC Intranet unterstützt nun ein vollständiges Alumni-System mit rollenbasierter Zugriffskontrolle und einem Freigabeprozess für Alumni-Mitglieder.

## Neue Rollen

### 1. Alumni (Rolle: `alumni`)
- **Zugriffslevel:** Lesezugriff (Level 1)
- **Berechtigungen:**
  - Inventar ansehen (nur lesen)
  - Dashboard ansehen
  - Eigenes Profil verwalten
  - **KEINE** Berechtigung zum Erstellen, Bearbeiten oder Löschen von Inventarartikeln
  
- **Besonderheit - Alumni-Validierung:**
  - Bei der Registrierung als Alumni wird das Feld `is_alumni_validated` auf `FALSE` gesetzt
  - Alumni-Benutzer haben zunächst keinen Zugriff auf interne Alumni-Netzwerkdaten
  - Ein Vorstandsmitglied muss das Profil manuell freigeben (validieren)
  - Nach der Freigabe wird `is_alumni_validated` auf `TRUE` gesetzt

### 2. Alumni-Vorstand (Rolle: `alumni_board`)
- **Zugriffslevel:** Vorstandsebene (Level 3)
- **Berechtigungen:**
  - Alle Berechtigungen eines Vorstands
  - Spezielle Verwaltung von Alumni-Angelegenheiten
  - Inventar vollständig verwalten
  - Benutzer verwalten (wenn Admin-Rechte)

### 3. Bestehende Rollen (unverändert)
- **Mitglied (member):** Lesezugriff
- **Ressortleiter (manager):** Inventar bearbeiten
- **Vorstand (board):** Volle Verwaltung
- **Administrator (admin):** Vollzugriff auf alle Funktionen

## Rollenhierarchie

```
Level 1 (Lesezugriff):
  ├── member (Mitglied)
  └── alumni (Alumni - benötigt Validierung)

Level 2 (Bearbeiten):
  └── manager (Ressortleiter)

Level 3 (Vorstand):
  ├── board (Vorstand)
  └── alumni_board (Alumni-Vorstand)

Level 4 (Vollzugriff):
  └── admin (Administrator)
```

## Alumni-Registrierungsprozess

### 1. Einladung erstellen (Admin)
1. Admin geht zu **Admin → Benutzerverwaltung**
2. Gibt E-Mail-Adresse des Alumni ein
3. Wählt Rolle "Alumni" aus
4. Klickt auf "Einladung senden"
5. System generiert 64-Zeichen kryptografischen Token
6. Admin sendet den Einladungslink an den Alumni

### 2. Registrierung (Alumni)
1. Alumni öffnet den Einladungslink
2. Sieht Hinweis: "Ihr Profil wird nach der Registrierung vom Vorstand manuell geprüft und freigeschaltet"
3. Erstellt Passwort (mindestens 8 Zeichen)
4. Konto wird erstellt mit `is_alumni_validated = FALSE`
5. Alumni kann sich einloggen, hat aber eingeschränkten Zugriff

### 3. Validierung (Vorstand)
1. Vorstand/Admin geht zu **Admin → Benutzerverwaltung**
2. Sieht Alumni-Benutzer mit Status "Ausstehend" (gelb)
3. Prüft die Identität des Alumni
4. Klickt auf "Ausstehend" Button um Profil freizugeben
5. Status ändert sich zu "Verifiziert" (grün)
6. Alumni hat nun vollen Lesezugriff

## Berechtigungsprüfung im Code

### Standardprüfung (hierarchisch)
```php
// Prüft, ob Benutzer mindestens "manager" Berechtigung hat
if (AuthHandler::hasPermission('manager')) {
    // Zeige Bearbeiten-Buttons
    // Nur für: manager, board, alumni_board, admin
}
```

### Alumni-Validierung prüfen
```php
// Prüft, ob Alumni validiert ist
if (AuthHandler::isAlumniValidated()) {
    // Zeige interne Alumni-Netzwerkdaten
}
```

### Beispiel: Inventar-Seite
```php
// Alle authentifizierten Benutzer können Inventar sehen
if (AuthHandler::isAuthenticated()) {
    // Zeige Inventarliste
}

// Nur Manager und höher können bearbeiten
if (AuthHandler::hasPermission('manager')) {
    // Zeige "Neuer Artikel" Button
    // Zeige "Bearbeiten" Buttons
}
```

## Admin-Interface

### Benutzerliste
Die Benutzerverwaltung zeigt nun:
- **Rolle-Dropdown:** Enthält alle 6 Rollen (member, alumni, manager, alumni_board, board, admin)
- **Status-Spalte:** 
  - 2FA-Badge (grün) wenn aktiviert
  - Alumni-Status (gelb "Ausstehend" / grün "Verifiziert") für Alumni-Benutzer
- **Alumni-Validierung:** Toggle-Button zum Freigeben/Sperren von Alumni-Profilen

### Einladungsformular
Neue Rollen im Dropdown:
- Mitglied
- Alumni ⭐ NEU
- Ressortleiter
- Alumni-Vorstand ⭐ NEU
- Vorstand
- Administrator

## Sicherheitshinweise

### Token-Sicherheit
- Tokens sind 64 Zeichen lang (kryptografisch sicher)
- Tokens laufen nach 7 Tagen ab
- Jeder Token kann nur einmal verwendet werden
- Tokens sind an eine spezifische E-Mail-Adresse gebunden

### Alumni-Validierung
- Alumni müssen manuell freigegeben werden
- Schützt vor unbefugtem Zugriff auf Alumni-Netzwerkdaten
- Vorstand kann Freigaben jederzeit widerrufen
- Alle Aktionen werden im Audit-Log protokolliert

## Neue Standorte

Das System unterstützt nun die folgenden neuen Lagerräume:

- **H-1.88:** Lagerraum H-1.88
- **H-1.87:** Lagerraum H-1.87

Diese Standorte sind automatisch in der Datenbank verfügbar und können im Inventarsystem verwendet werden.

## API-Referenz

### AuthHandler::hasPermission($requiredRole)
Prüft, ob der aktuelle Benutzer die erforderliche Berechtigung hat.

**Parameter:**
- `$requiredRole` (string): Minimale erforderliche Rolle ('member', 'manager', 'board', 'admin')

**Rückgabe:**
- `bool`: true wenn Berechtigung vorhanden, sonst false

**Beispiel:**
```php
if (AuthHandler::hasPermission('manager')) {
    // Benutzer ist manager, board, alumni_board oder admin
}
```

### AuthHandler::isAlumniValidated()
Prüft, ob ein Alumni-Benutzer validiert ist.

**Rückgabe:**
- `bool`: true wenn Benutzer kein Alumni ist oder validiert wurde, sonst false

**Beispiel:**
```php
if ($user['role'] === 'alumni' && !AuthHandler::isAlumniValidated()) {
    echo "Ihr Profil wartet auf Freigabe durch den Vorstand.";
}
```

## Migration für bestehende Installationen

Für bestehende Installationen steht ein Migrationsskript zur Verfügung:

```bash
mysql -h <host> -u <user> -p < sql/migrations/001_add_alumni_roles_and_locations.sql
```

Das Skript:
1. Fügt die neuen Standorte H-1.88 und H-1.87 hinzu
2. Erweitert das Rollen-ENUM um 'alumni' und 'alumni_board'
3. Fügt das Feld `is_alumni_validated` zur users-Tabelle hinzu
4. Aktualisiert die invitation_tokens-Tabelle

**Wichtig:** Backup der Datenbanken vor der Migration erstellen!

## Häufig gestellte Fragen (FAQ)

### Warum müssen Alumni validiert werden?
Alumni-Mitglieder haben möglicherweise keinen aktuellen Bezug zur Organisation mehr. Die manuelle Validierung stellt sicher, dass nur vertrauenswürdige Alumni Zugriff auf interne Daten erhalten.

### Kann ich Alumni automatisch validieren?
Nein. Der manuelle Validierungsprozess ist ein Sicherheitsfeature. Admins müssen Alumni-Profile explizit freigeben.

### Was passiert, wenn ich die Validierung widerrufe?
Der Alumni-Benutzer behält seinen Zugang zum System, aber der Zugriff auf interne Alumni-Netzwerkdaten wird gesperrt.

### Können Alumni zu anderen Rollen befördert werden?
Ja. Ein Admin kann die Rolle eines Alumni jederzeit ändern (z.B. zu 'manager' oder 'board').

## Support

Bei Fragen oder Problemen:
1. Überprüfen Sie die Audit-Logs (Admin → Audit-Logs)
2. Kontaktieren Sie den System-Administrator
3. Konsultieren Sie die Hauptdokumentation (README.md)
