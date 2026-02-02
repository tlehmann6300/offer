# Einladungs-Management UI Mockup

## Seiten-Layout

### Benutzerverwaltung mit Tab-Navigation

```
┌─────────────────────────────────────────────────────────────────┐
│ 👥 Benutzerverwaltung                                           │
│ 5 Benutzer gesamt                                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ [📋 Benutzerliste] [✉️  Einladungen]                           │
│ ══════════════════                                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Tab: Einladungen

### Abschnitt 1: Einladung erstellen

```
┌─────────────────────────────────────────────────────────────────┐
│ ✉️  Einladungs-Management                                       │
│ Erstellen Sie Einladungslinks für neue Mitglieder und Alumni   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ 🔗 Einladung erstellen                                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│ │ E-Mail       │  │ Rolle        │  │              │          │
│ │ ____________ │  │ [Mitglied ▼] │  │ ✨ Link     │          │
│ │              │  │              │  │    erstellen │          │
│ └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                 │
│ ┌─────────────────────────────────────────────────────────────┐│
│ │ ✓ Einladungslink erfolgreich erstellt!                     ││
│ │ E-Mail: user@example.com | Rolle: Mitglied                 ││
│ │                                                             ││
│ │ https://intranet.ibc.de/pages/auth/register.php?token=... ││
│ │                                              [📋 Kopieren] ││
│ └─────────────────────────────────────────────────────────────┘│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Abschnitt 2: Offene Einladungen

```
┌─────────────────────────────────────────────────────────────────┐
│ 📋 Offene Einladungen                    [🔄 Aktualisieren]    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ ┌───────────────────────────────────────────────────────────┐  │
│ │ E-Mail │ Rolle │ Erstellt │ Läuft ab │ Von │ Link │ Aktion││
│ ├───────────────────────────────────────────────────────────┤  │
│ │ ✉ user1@ │[Mitglied]│ 01.02. │ 08.02. │ admin@ │[📋]│[🗑️]││
│ │ ✉ user2@ │[Alumni] │ 02.02. │ 09.02. │ board@ │[📋]│[🗑️]││
│ └───────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Interaktionen

### 1. Einladung erstellen
**Schritte:**
1. Benutzer gibt E-Mail ein
2. Benutzer wählt Rolle aus Dropdown
3. Klick auf "Link erstellen" Button
4. Loading-Spinner erscheint
5. Erfolgsbox mit generiertem Link wird angezeigt
6. "Kopieren"-Button ermöglicht Kopieren in Zwischenablage
7. Tabelle "Offene Einladungen" wird automatisch aktualisiert

**Validierungen:**
- E-Mail-Format wird geprüft
- Fehlermeldung bei ungültiger E-Mail
- Fehlermeldung wenn Benutzer bereits existiert
- Fehlermeldung wenn Einladung bereits existiert

### 2. Link kopieren
**Schritte:**
1. Klick auf "Kopieren" Button (generierter Link)
2. Button zeigt "✓ Kopiert!" für 2 Sekunden
3. Link ist in Zwischenablage
4. Erfolgsbenachrichtigung erscheint

**Schritte (aus Tabelle):**
1. Klick auf "📋" Icon in Link-Spalte
2. Erfolgsbenachrichtigung: "Link in die Zwischenablage kopiert"

### 3. Einladung löschen
**Schritte:**
1. Klick auf "🗑️" Icon in Aktionen-Spalte
2. Bestätigungsdialog: "Möchten Sie diese Einladung wirklich löschen?"
3. Bei Bestätigung: Einladung wird gelöscht
4. Erfolgsbenachrichtigung: "Einladung erfolgreich gelöscht"
5. Tabelle wird automatisch aktualisiert

### 4. Einladungen aktualisieren
**Schritte:**
1. Klick auf "🔄 Aktualisieren" Button
2. Loading-Spinner erscheint
3. Tabelle wird neu geladen
4. Aktuelle Einladungen werden angezeigt

## Responsive Design

### Desktop (> 768px)
- 3-spaltige Form-Layout (E-Mail, Rolle, Button)
- Vollständige Tabelle mit allen Spalten
- Großzügige Abstände

### Tablet (768px - 1024px)
- 2-spaltige Form-Layout
- Tabelle mit horizontalem Scroll
- Kompakte Abstände

### Mobile (< 768px)
- 1-spaltige Form-Layout
- Tabelle mit horizontalem Scroll
- Minimale Abstände
- Stacked Layout für Karten

## Farb-Schema (Tailwind CSS)

- **Primary:** Purple-600 (#9333EA)
- **Success:** Green-600 (#16A34A)
- **Error:** Red-600 (#DC2626)
- **Warning:** Yellow-600 (#CA8A04)
- **Info:** Blue-600 (#2563EB)
- **Background:** Gray-50 (#F9FAFB)
- **Borders:** Gray-300 (#D1D5DB)
- **Text:** Gray-800 (#1F2937)

## Icons (Font Awesome)

- 👥 `fa-users` - Benutzerverwaltung
- ✉️ `fa-envelope` - Einladungen
- 🔗 `fa-link` - Link erstellen
- ✨ `fa-magic` - Magic/Erstellen
- 📋 `fa-copy` - Kopieren
- 🗑️ `fa-trash` - Löschen
- 🔄 `fa-sync-alt` - Aktualisieren
- ✓ `fa-check-circle` - Erfolg
- ⚠️ `fa-exclamation-circle` - Fehler
- 📊 `fa-list` - Liste
- 🔍 `fa-inbox` - Leer
- ⏳ `fa-spinner` - Loading
