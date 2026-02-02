# Event & Helper System

## Übersicht

Das Event & Helper System ist eine Erweiterung des IBC Intranets, die es ermöglicht, Events zu verwalten und Helfer für diese Events zu koordinieren. Das System basiert auf der bestehenden Datenbankstruktur und Authentifizierung.

## Hauptfunktionen

### 1. Event Management
- **CRUD-Operationen**: Erstellen, Lesen, Aktualisieren und Löschen von Events
- **Event-Status**: planned, open, closed, running, past
- **Externe Events**: Unterstützung für externe Events mit Links
- **Rollenverwaltung**: Definieren, welche Rollen an einem Event teilnehmen dürfen

### 2. Helper System
- **Helper Types**: Kategorien von Helfern (z.B., "Aufbau", "Abbau", "Catering")
- **Time Slots**: Zeitfenster für Helfer mit benötigter Anzahl
- **Signup Management**: Verwaltung von Anmeldungen und Wartelisten
- **Alumni Restriction**: Alumni können KEINE Helper-Slots sehen oder buchen

### 3. Locking Mechanism
- **Event Locking**: Verhindert gleichzeitige Bearbeitungen
- **Auto-Timeout**: Locks laufen nach 15 Minuten automatisch ab
- **Lock Management**: checkLock(), acquireLock(), releaseLock()

### 4. Audit Logging
- **Event History**: Alle Änderungen werden in event_history protokolliert
- **Change Details**: JSON-Format für detaillierte Änderungsinformationen
- **Benutzer-Tracking**: Wer hat wann welche Änderung vorgenommen

## Datenbankstruktur

### Tabellen

#### events
Haupttabelle für alle Events
- `id`: Primärschlüssel
- `title`: Event-Titel
- `description`: Beschreibung
- `location`: Veranstaltungsort
- `start_time`, `end_time`: Zeitraum
- `contact_person`: Ansprechpartner
- `status`: planned, open, closed, running, past
- `is_external`: Boolean für externe Events
- `external_link`: Link zu externen Events
- `needs_helpers`: Boolean für Helper-Bedarf
- `locked_by`, `locked_at`: Locking-Mechanismus

#### event_roles
Verknüpfung zwischen Events und erlaubten Rollen
- `event_id`: Referenz zu events
- `role`: alumni, member, board, alumni_board, manager, admin

#### event_helper_types
Kategorien von Helfern für ein Event
- `event_id`: Referenz zu events
- `title`: z.B., "Aufbau", "Abbau"
- `description`: Beschreibung der Aufgabe

#### event_slots
Zeitfenster für Helper Types
- `helper_type_id`: Referenz zu event_helper_types
- `start_time`, `end_time`: Zeitfenster
- `quantity_needed`: Benötigte Anzahl Helfer

#### event_signups
Anmeldungen für Events und Slots
- `event_id`: Referenz zu events
- `user_id`: Benutzer-ID
- `slot_id`: Optional, für Helper-Slots
- `status`: confirmed, waitlist, cancelled

#### event_history
Audit-Log für alle Änderungen
- `event_id`: Referenz zu events
- `user_id`: Wer hat geändert
- `change_type`: Art der Änderung
- `change_details`: JSON mit Details

## API / Model Usage

### Event erstellen

```php
require_once 'includes/models/Event.php';

$eventData = [
    'title' => 'IBC Summer Festival',
    'description' => 'Annual summer event',
    'location' => 'Campus Grounds',
    'start_time' => '2026-07-15 14:00:00',
    'end_time' => '2026-07-15 20:00:00',
    'contact_person' => 'John Doe',
    'status' => 'open',
    'is_external' => false,
    'needs_helpers' => true,
    'allowed_roles' => ['member', 'board', 'manager']
];

$eventId = Event::create($eventData, $userId);
```

### Event abrufen

```php
// Single event
$event = Event::getById($eventId);

// Multiple events with filters
$openEvents = Event::getEvents([
    'status' => 'open',
    'needs_helpers' => true,
    'include_helpers' => true
], $userRole);
```

### Helper Type und Slots erstellen

```php
// Create helper type
$setupTypeId = Event::createHelperType($eventId, 'Aufbau', 'Setup before event', $userId);

// Create time slot
$slotId = Event::createSlot(
    $setupTypeId,
    '2026-07-15 12:00:00',
    '2026-07-15 14:00:00',
    5, // 5 helpers needed
    $userId,
    $eventId
);
```

### Signup für Event

```php
// Sign up for event (general participation)
$signup = Event::signup($eventId, $userId, null, $userRole);

// Sign up for helper slot (NOT allowed for alumni!)
$signup = Event::signup($eventId, $userId, $slotId, $userRole);
```

### Locking verwenden

```php
// Check if event is locked
$lockStatus = Event::checkLock($eventId, $userId);

// Acquire lock
$lockResult = Event::acquireLock($eventId, $userId);

// Do your edits...

// Release lock
$releaseResult = Event::releaseLock($eventId, $userId);
```

## Wichtige Sicherheitsregeln

### Alumni Restrictions
**KRITISCH**: Alumni dürfen NIEMALS Helper-Slots sehen oder buchen!

Das System implementiert diese Regel auf mehreren Ebenen:

1. **In getEvents()**: 
   - Alumni sehen `needs_helpers = false`
   - `helper_types` array ist immer leer für Alumni

2. **In signup()**:
   - Exception wird geworfen wenn Alumni versuchen, sich für Slots anzumelden
   - Fehlermeldung: "Alumni users are not allowed to sign up for helper slots"

3. **Role-based filtering**:
   - Events können Rollen definieren, die teilnehmen dürfen
   - Filtrierung erfolgt in getEvents()

## Installation

### 1. SQL Migration ausführen

```bash
mysql -h <host> -u <user> -p < sql/migrations/004_add_event_system.sql
```

### 2. Model ist bereits verfügbar

Das Event-Model ist unter `includes/models/Event.php` verfügbar und kann direkt verwendet werden:

```php
require_once 'includes/models/Event.php';
```

## Testing

### Static Validation
```bash
php tests/test_event_model_static.php
```

### Integration Tests (benötigt Datenbankverbindung)
```bash
php tests/test_event_model.php
```

## Verwendungsbeispiele

### Beispiel 1: Internes Event ohne Helfer
```php
$eventData = [
    'title' => 'Monatliches Team Meeting',
    'start_time' => '2026-03-01 18:00:00',
    'end_time' => '2026-03-01 20:00:00',
    'status' => 'open',
    'needs_helpers' => false,
    'allowed_roles' => ['member', 'board']
];
$eventId = Event::create($eventData, $userId);
```

### Beispiel 2: Event mit Helfern
```php
// Event erstellen
$eventData = [
    'title' => 'IBC Sommerfest',
    'start_time' => '2026-07-15 14:00:00',
    'end_time' => '2026-07-15 20:00:00',
    'needs_helpers' => true,
    'allowed_roles' => ['member', 'board', 'manager', 'alumni']
];
$eventId = Event::create($eventData, $userId);

// Helper Types erstellen
$setupId = Event::createHelperType($eventId, 'Aufbau', 'Setup', $userId);
$cleanupId = Event::createHelperType($eventId, 'Abbau', 'Cleanup', $userId);

// Slots erstellen
Event::createSlot($setupId, '2026-07-15 12:00:00', '2026-07-15 14:00:00', 5, $userId, $eventId);
Event::createSlot($cleanupId, '2026-07-15 20:00:00', '2026-07-15 22:00:00', 3, $userId, $eventId);

// Member kann sich anmelden (Alumni NICHT für Slots!)
Event::signup($eventId, $memberId, $slotId, 'member'); // OK
Event::signup($eventId, $alumniId, null, 'alumni');    // OK - general participation
Event::signup($eventId, $alumniId, $slotId, 'alumni'); // FEHLER - Alumni cannot book slots
```

### Beispiel 3: Event bearbeiten mit Locking
```php
// Lock erwerben
$lockResult = Event::acquireLock($eventId, $userId);
if ($lockResult['success']) {
    // Event aktualisieren
    Event::update($eventId, [
        'status' => 'running',
        'description' => 'Updated description'
    ], $userId);
    
    // Lock freigeben
    Event::releaseLock($eventId, $userId);
}
```

## Unterstützung

Bei Fragen oder Problemen:
1. Prüfen Sie die Test-Dateien für Verwendungsbeispiele
2. Überprüfen Sie die event_history für Audit-Logs
3. Kontaktieren Sie den System-Administrator

## Lizenz

Teil des IBC Intranet Systems
