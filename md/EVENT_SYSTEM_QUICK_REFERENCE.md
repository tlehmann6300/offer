# Event & Helper System - Quick Reference

## Quick Start Guide

### Installation

1. **Run the SQL migration:**
   ```bash
   mysql -h <host> -u <user> -p dbs15161271 < sql/migrations/004_add_event_system.sql
   ```

2. **Include the Event model in your PHP files:**
   ```php
   require_once 'includes/models/Event.php';
   ```

## Common Use Cases

### 1. Create a Simple Event (No Helpers)
```php
$eventData = [
    'title' => 'Monthly Team Meeting',
    'description' => 'Regular monthly sync',
    'location' => 'Conference Room A',
    'start_time' => '2026-03-15 18:00:00',
    'end_time' => '2026-03-15 20:00:00',
    'status' => 'open',
    'needs_helpers' => false,
    'allowed_roles' => ['member', 'board']
];

$eventId = Event::create($eventData, $currentUserId);
```

### 2. Create Event with Helper Slots
```php
// Step 1: Create the event
$eventData = [
    'title' => 'IBC Sommerfest 2026',
    'description' => 'Annual summer celebration',
    'location' => 'Campus Grounds',
    'start_time' => '2026-07-15 14:00:00',
    'end_time' => '2026-07-15 22:00:00',
    'status' => 'open',
    'needs_helpers' => true,
    'allowed_roles' => ['member', 'board', 'manager', 'alumni']
];
$eventId = Event::create($eventData, $currentUserId);

// Step 2: Create helper types
$setupId = Event::createHelperType($eventId, 'Aufbau', 'Zelte und Tische aufbauen', $currentUserId);
$cleanupId = Event::createHelperType($eventId, 'Abbau', 'Aufräumen nach dem Event', $currentUserId);

// Step 3: Create time slots
Event::createSlot($setupId, '2026-07-15 12:00:00', '2026-07-15 14:00:00', 5, $currentUserId, $eventId);
Event::createSlot($cleanupId, '2026-07-15 22:00:00', '2026-07-16 00:00:00', 3, $currentUserId, $eventId);
```

### 3. List Events (with Role Filtering)
```php
// Get all open events for the current user
$openEvents = Event::getEvents([
    'status' => 'open',
    'include_helpers' => true
], $currentUserRole);

foreach ($openEvents as $event) {
    echo "{$event['title']} - {$event['start_time']}\n";
    
    if ($event['needs_helpers'] && !empty($event['helper_types'])) {
        foreach ($event['helper_types'] as $helperType) {
            echo "  Helper needed: {$helperType['title']}\n";
        }
    }
}
```

### 4. Sign Up for Event
```php
// Sign up for general participation
$result = Event::signup($eventId, $currentUserId, null, $currentUserRole);

// Sign up for specific helper slot (NOT allowed for Alumni!)
$result = Event::signup($eventId, $currentUserId, $slotId, $currentUserRole);

if ($result['status'] === 'confirmed') {
    echo "Successfully registered!";
} else if ($result['status'] === 'waitlist') {
    echo "Added to waitlist - slot is full";
}
```

### 5. Edit Event with Locking
```php
// Acquire lock before editing
$lockResult = Event::acquireLock($eventId, $currentUserId);

if ($lockResult['success']) {
    // Update the event
    Event::update($eventId, [
        'description' => 'Updated event description',
        'status' => 'running'
    ], $currentUserId);
    
    // Release lock when done
    Event::releaseLock($eventId, $currentUserId);
} else {
    echo "Event is locked by another user";
}
```

### 6. View Event History
```php
$history = Event::getHistory($eventId, 20); // Last 20 changes

foreach ($history as $entry) {
    $details = json_decode($entry['change_details'], true);
    echo "{$entry['timestamp']}: {$entry['change_type']}\n";
    echo "  By user ID: {$entry['user_id']}\n";
    echo "  Details: " . print_r($details, true) . "\n";
}
```

### 7. Get User's Signups
```php
$mySignups = Event::getUserSignups($currentUserId);

foreach ($mySignups as $signup) {
    echo "{$signup['event_title']} - {$signup['start_time']}\n";
    if ($signup['slot_id']) {
        echo "  (Helper slot)\n";
    }
}
```

## Important Rules

### ⚠️ Alumni Restrictions
Alumni users have special restrictions:
- **CANNOT** see helper slots or needs_helpers information
- **CANNOT** sign up for helper slots
- **CAN** see events they're allowed to attend
- **CAN** sign up for general event participation (no slot_id)

Example:
```php
// This will work:
Event::signup($eventId, $alumniUserId, null, 'alumni');

// This will throw an exception:
Event::signup($eventId, $alumniUserId, $slotId, 'alumni');
// Exception: "Alumni users are not allowed to sign up for helper slots"
```

### 🔒 Locking Rules
- Locks automatically expire after 15 minutes
- Only the user who acquired the lock can release it
- Trying to edit a locked event will throw an exception
- Always release locks when done editing

### 📝 Event Status Values
- `planned`: Event is being planned
- `open`: Event is open for registration
- `closed`: Registration closed
- `running`: Event is currently happening
- `past`: Event has ended

### 👥 User Roles
- `alumni`: Alumni members (restricted helper access)
- `member`: Regular members
- `manager`: Managers
- `board`: Board members
- `alumni_board`: Alumni board members
- `admin`: Administrators

## Testing

### Run Static Tests (no database needed)
```bash
php tests/test_event_model_static.php
```

### Run Integration Tests (requires database)
```bash
php tests/test_event_model.php
```

## Documentation

For detailed documentation, see:
- `md/EVENT_SYSTEM.md` - Complete system documentation
- `sql/migrations/README.md` - Migration guide
- `sql/migrations/004_add_event_system.sql` - Database schema

## Support

For issues or questions:
1. Check the test files for examples
2. Review the event_history table for audit logs
3. Verify user roles and permissions
4. Contact system administrator
