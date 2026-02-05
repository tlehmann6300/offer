# Event Registration Implementation

## Overview
This implementation adds simplified event registration functionality using the `event_registrations` table and `MailService::sendEventConfirmation` method.

## Files Added/Modified

### New Files
1. **`api/event_signup_simple.php`** - Standalone simple event registration endpoint
2. **`sql/migrations/add_event_registrations_table.sql`** - Database migration for event_registrations table
3. **`apply_event_registrations_migration.php`** - Migration runner script

### Modified Files
1. **`api/event_signup.php`** - Added 'simple_register' action for backward compatibility
2. **`src/MailService.php`** - Added `sendEventConfirmation()` method

## Database Setup

### Run Migration
```bash
php apply_event_registrations_migration.php
```

This creates the `event_registrations` table with the following structure:
```sql
CREATE TABLE event_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmed', 'cancelled') NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id)
);
```

## API Usage

### Option 1: Using Standalone Endpoint (Recommended for New Code)

**Endpoint:** `POST /api/event_signup_simple.php`

**Request:**
```json
{
    "event_id": 123
}
```

**Success Response:**
```json
{
    "success": true,
    "message": "Erfolgreich angemeldet"
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error message in German"
}
```

### Option 2: Using Existing Endpoint with New Action

**Endpoint:** `POST /api/event_signup.php`

**Request:**
```json
{
    "action": "simple_register",
    "event_id": 123
}
```

**Response:** Same as Option 1

## Features

### Authentication
- ✅ Checks if user is logged in using `Auth::check()`
- ✅ Returns 401 Unauthorized if not authenticated

### Validation
- ✅ Validates JSON input format
- ✅ Checks for required `event_id` parameter
- ✅ Verifies event exists in database
- ✅ Prevents duplicate registrations

### Database Operations
- ✅ Saves registration to `event_registrations` table
- ✅ Updates existing cancelled registration if user re-registers
- ✅ Uses prepared statements to prevent SQL injection

### Email Notification
- ✅ Sends confirmation email using `MailService::sendEventConfirmation()`
- ✅ Email includes event details (title, date/time, location, contact person, description)
- ✅ Gracefully handles email failures (logs error but doesn't fail registration)

### Error Handling
- ✅ Try-catch block for comprehensive error handling
- ✅ Appropriate HTTP status codes (401, 405, 400)
- ✅ User-friendly error messages in German

## Email Confirmation

The `sendEventConfirmation` method sends a professionally formatted email with:
- Event title
- Date and time (formatted for single-day or multi-day events)
- Location (if provided)
- Contact person (if provided)
- Event description (if provided)
- Link to events page
- IBC corporate design with logo

## Testing

Run the test suite:
```bash
php test_event_signup_implementation.php
```

This validates:
- File existence
- PHP syntax
- Required includes
- Authentication checks
- JSON input reading
- Database table usage
- Email confirmation calls
- Success messages
- Error handling

## Security

See `SECURITY_SUMMARY.md` for detailed security analysis.

Key security features:
- ✅ Authentication required
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (htmlspecialchars)
- ✅ Input validation
- ✅ Error handling
- ✅ HTTP method validation

## Backward Compatibility

The implementation maintains full backward compatibility:
- Existing `event_signup.php` functionality remains unchanged
- New 'simple_register' action added alongside existing 'signup' and 'cancel' actions
- Existing `event_signups` table continues to work for helper slot signups
- New `event_registrations` table for simple event registrations

## Migration from Complex to Simple Registration

If you want to migrate from the complex event_signups system to the simple event_registrations system:

1. Update frontend to call `event_signup_simple.php` or use `action: 'simple_register'`
2. No database migration needed - both tables can coexist
3. Gradually migrate events from helper slot system to simple registration

## Troubleshooting

### "Event-ID fehlt" Error
- Ensure `event_id` is included in the POST request body

### "Nicht authentifiziert" Error (401)
- User must be logged in before registering
- Check session is active and valid

### "Sie sind bereits für dieses Event angemeldet" Error
- User already has a confirmed registration for this event
- They need to cancel first before re-registering

### Email not received
- Check email configuration in `.env` file
- Check server logs for email errors
- Email failures don't prevent successful registration

## Support

For issues or questions, please contact the development team.
