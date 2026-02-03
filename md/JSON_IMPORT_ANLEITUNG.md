# JSON Bulk Import für Einladungen

## Übersicht

Mit dem JSON Bulk Import Feature können Administratoren und Vorstandsmitglieder mehrere Benutzereinladungen gleichzeitig erstellen, indem sie eine JSON-Datei hochladen.

## Zugriff

- **Berechtigung:** Board oder Admin
- **Navigation:** Admin-Bereich → Benutzerverwaltung → Tab "Einladungen"
- **Button:** "JSON Import" (oben rechts im Einladungs-Management)

## JSON-Format

Die JSON-Datei muss ein Array von Einladungsobjekten enthalten. Jedes Objekt benötigt:

- `email`: Gültige E-Mail-Adresse (erforderlich)
- `role`: Rolle des neuen Benutzers (erforderlich)

### Beispiel: Gültige JSON-Datei

```json
[
  {
    "email": "max.mustermann@example.com",
    "role": "member"
  },
  {
    "email": "erika.musterfrau@example.com",
    "role": "alumni"
  },
  {
    "email": "john.doe@example.com",
    "role": "manager"
  }
]
```

## Verfügbare Rollen

- `member` - Mitglied
- `alumni` - Alumni
- `manager` - Ressortleiter
- `alumni_board` - Alumni-Vorstand
- `board` - Vorstand
- `admin` - Administrator

## Verwendung

1. **JSON-Datei erstellen**: Erstellen Sie eine JSON-Datei mit den gewünschten Einladungen
2. **Import-Modal öffnen**: Klicken Sie auf "JSON Import"
3. **Datei auswählen**: Wählen Sie Ihre JSON-Datei aus (.json Format)
4. **Import starten**: Klicken Sie auf "Importieren"
5. **Ergebnisse prüfen**: Nach dem Import wird eine Zusammenfassung angezeigt

## Import-Ergebnisse

Nach dem Import erhalten Sie eine Zusammenfassung:

- **Gesamt**: Anzahl der Einträge in der JSON-Datei
- **Erfolgreich**: Anzahl erfolgreich erstellter Einladungen
- **Fehlgeschlagen**: Anzahl fehlgeschlagener Einladungen

Bei Fehlern wird eine detaillierte Fehlerliste angezeigt.

## Fehlerbehandlung

Das System prüft für jeden Eintrag:

1. **E-Mail-Format**: Muss gültig sein
2. **Rolle**: Muss eine der erlaubten Rollen sein
3. **Bestehender Benutzer**: E-Mail darf nicht bereits registriert sein
4. **Offene Einladung**: Es darf keine aktive Einladung für diese E-Mail existieren

Fehlerhafte Einträge werden übersprungen, ohne den gesamten Import zu stoppen.

## Beispiel-Fehlermeldungen

```
Zeile 2: Ungültige E-Mail-Adresse: invalid-email
Zeile 3: Ungültige Rolle: invalid_role
Zeile 4: Fehlende Felder (email oder role erforderlich)
Zeile 5: Benutzer existiert bereits: existing@example.com
Zeile 6: Offene Einladung existiert bereits: pending@example.com
```

## E-Mail-Versand

Für jede erfolgreich erstellte Einladung wird automatisch eine E-Mail mit dem Registrierungslink an den Empfänger gesendet.

## Performance

- Das System verwendet `set_time_limit(0)` für große Importe
- Jeder Eintrag wird sequenziell verarbeitet
- Es gibt keine praktische Obergrenze für die Anzahl der Einladungen

## Sicherheit

- CSRF-Token-Validierung
- Authentifizierungsprüfung
- Berechtigungsprüfung (nur Board/Admin)
- Dateityp-Validierung
- Input-Sanitization für alle Felder

## Testdaten

Im Repository sind Beispiel-JSON-Dateien verfügbar:

- `sample_invitations.json`: Gültige Testdaten
- `sample_invitations_with_errors.json`: Testdaten mit absichtlichen Fehlern
