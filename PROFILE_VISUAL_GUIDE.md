# Profile.php - Visuelle Übersicht der Änderungen

## 1. Neue Felder im Formular

### Geschlecht-Auswahl
```html
<div>
    <label>Geschlecht</label>
    <select name="gender">
        <option value="">Bitte wählen</option>
        <option value="m">Männlich</option>
        <option value="f">Weiblich</option>
        <option value="d">Divers</option>
    </select>
</div>
```

### Geburtstag-Eingabe
```html
<div>
    <label>Geburtstag</label>
    <input 
        type="date" 
        name="birthday" 
        max="<?php echo date('Y-m-d'); ?>"
    >
</div>
```

### Über mich mit Zeichenzähler
```html
<div>
    <label>
        Über mich
        <span>(<span id="char-count">0</span>/400 Zeichen)</span>
    </label>
    <textarea 
        id="about_me"
        name="about_me" 
        maxlength="400"
    ></textarea>
</div>
```

## 2. Studium-Felder nach Rolle

### Für Studierende (member/candidate/head)

**Abschnitt: "Aktuelles Studium"**

```
┌─────────────────────────────────────────┐
│ Aktuelles Studium                       │
├─────────────────────────────────────────┤
│ Bachelor-Studiengang *                  │
│ [_____________________]                 │
│                                         │
│ Bachelor-Semester                       │
│ [_____________________]                 │
│                                         │
│ Master-Studiengang (optional)           │
│ [_____________________]                 │
│                                         │
│ Master-Semester (optional)              │
│ [_____________________]                 │
└─────────────────────────────────────────┘
```

### Für Alumni (alumni/alumni_board)

**Abschnitt: "Absolviertes Studium"**

```
┌─────────────────────────────────────────┐
│ Absolviertes Studium                    │
├─────────────────────────────────────────┤
│ Bachelor-Studiengang *                  │
│ [_____________________]                 │
│                                         │
│ Bachelor-Abschlussjahr                  │
│ [_____________________]                 │
│                                         │
│ Master-Studiengang (optional)           │
│ [_____________________]                 │
│                                         │
│ Master-Abschlussjahr (optional)         │
│ [_____________________]                 │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ Berufliche Informationen                │
├─────────────────────────────────────────┤
│ Aktueller Arbeitgeber                   │
│ [_____________________]                 │
│                                         │
│ Position                                │
│ [_____________________]                 │
│                                         │
│ Branche                                 │
│ [_____________________]                 │
└─────────────────────────────────────────┘
```

## 3. E-Mail-Änderungsschutz

### Ablauf

```
Benutzer ändert E-Mail
         ↓
Formular wird abgeschickt
         ↓
    Ist Alumni? ──YES→ Speichern
         ↓ NO
E-Mail geändert? ──NO→ Speichern
         ↓ YES
         ┌────────────────────────────────────┐
         │ ⚠️ Bestätigung                      │
         ├────────────────────────────────────┤
         │ Willst du deine E-Mail wirklich    │
         │ ändern? Dies ändert deinen         │
         │ Login-Namen.                       │
         │                                    │
         │    [Abbrechen]     [OK]           │
         └────────────────────────────────────┘
              ↓                    ↓
          Abbruch              Speichern
```

## 4. Datenfluss

### Speichern von Profil-Daten

```
Frontend (Form Submit)
         ↓
POST-Handler empfängt Daten
         ↓
    ┌────────────────────────────┐
    │ 1. Update users-Tabelle    │
    │    - gender                 │
    │    - birthday               │
    │    - about_me               │
    └────────────────────────────┘
         ↓
    ┌────────────────────────────┐
    │ 2. Update alumni_profiles   │
    │    - studiengang            │
    │    - semester               │
    │    - angestrebter_abschluss │
    │    - graduation_year        │
    │    - (company, position...) │
    └────────────────────────────┘
         ↓
    ┌────────────────────────────┐
    │ 3. Reload user data         │
    │ 4. Reload profile data      │
    │ 5. Sync gender & birthday   │
    └────────────────────────────┘
         ↓
Erfolg-Meldung anzeigen
```

## 5. JavaScript-Features

### Zeichenzähler
```javascript
aboutMeTextarea.addEventListener('input', function() {
    charCount.textContent = this.value.length;
    // Zeigt: (234/400 Zeichen)
});
```

### E-Mail-Bestätigung
```javascript
if (emailChanged && !isAlumniRole) {
    const confirmed = confirm(
        'Willst du deine E-Mail wirklich ändern? ' +
        'Dies ändert deinen Login-Namen.'
    );
    if (!confirmed) {
        e.preventDefault(); // Abbruch
    }
}
```

## 6. Sicherheitsverbesserungen

### XSS-Schutz
**Vorher:**
```php
// ❌ UNSICHER
const email = '<?php echo addslashes($email); ?>';
```

**Nachher:**
```php
// ✅ SICHER
const email = <?php echo json_encode($email, 
    JSON_HEX_TAG | JSON_HEX_AMP | 
    JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
```

### Input-Validierung
```php
// Geburtstag: Nicht in der Zukunft
max="<?php echo date('Y-m-d'); ?>"

// Über mich: Max 400 Zeichen
mb_substr(trim($_POST['about_me'] ?? ''), 0, 400)
```

## 7. Responsive Layout

Die Felder verwenden ein 2-Spalten-Grid auf Desktop:

```
┌────────────────────┬────────────────────┐
│ Vorname *          │ Nachname *         │
├────────────────────┼────────────────────┤
│ E-Mail (Profil) *  │ Telefon            │
├────────────────────┼────────────────────┤
│ LinkedIn URL       │ Xing URL           │
├────────────────────┼────────────────────┤
│ Geschlecht         │ Geburtstag         │
├────────────────────┴────────────────────┤
│ Profilbild                              │
├─────────────────────────────────────────┤
│ [Studienfelder - je nach Rolle]         │
└─────────────────────────────────────────┘
```

Auf Mobil werden die Felder untereinander angezeigt (1 Spalte).

## 8. Datenbank-Mapping

### Für Studierende
```
HTML-Field              → DB-Column
─────────────────────────────────────
bachelor_studiengang   → studiengang
bachelor_semester      → semester
master_studiengang     → angestrebter_abschluss
master_semester        → graduation_year
```

### Für Alumni
```
HTML-Field              → DB-Column
─────────────────────────────────────
bachelor_studiengang   → studiengang
bachelor_year          → semester
master_studiengang     → angestrebter_abschluss
master_year            → graduation_year
company                → company
position               → position
industry               → industry
```

## 9. Fehlermeldungen

### Erfolg
```
┌───────────────────────────────────────┐
│ ✓ Profil erfolgreich aktualisiert     │
└───────────────────────────────────────┘
```

### Fehler
```
┌───────────────────────────────────────┐
│ ⚠ Fehler beim Aktualisieren des Profils│
└───────────────────────────────────────┘
```

## 10. Browser-Kompatibilität

- ✅ Chrome/Edge (modern)
- ✅ Firefox (modern)
- ✅ Safari (modern)
- ✅ Mobile Browsers

**Benötigte Features:**
- Date input type
- FormData API
- addEventListener
- ES6 JavaScript (const, arrow functions, template literals)

## Zusammenfassung

Alle visuellen und funktionalen Änderungen sind vollständig implementiert und bereit für den produktiven Einsatz.
