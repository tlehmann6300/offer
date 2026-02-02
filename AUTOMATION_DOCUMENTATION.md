# Event Automation and Email System

This document describes the automated email and calendar integration features added to the event management system.

## Features Implemented

### 1. Calendar Service (iCal Generator)

**Location:** `src/CalendarService.php`

The CalendarService provides functionality to generate iCal (.ics) files and Google Calendar links for events.

#### Methods:

- `generateICS($event, $slot = null)`: Generates an ICS file string
  - For full events: Uses event start/end times
  - For specific slots: Uses slot start/end times
  - Returns: Valid iCal format string with VEVENT component
  
- `generateGoogleCalendarLink($event, $slot = null)`: Generates a Google Calendar URL
  - Creates a pre-filled "Add to Calendar" link
  - Works for both full events and specific slots
  - Returns: Google Calendar URL

#### Usage Example:

```php
require_once __DIR__ . '/src/CalendarService.php';

// For a full event
$icsContent = CalendarService::generateICS($event);
$googleLink = CalendarService::generateGoogleCalendarLink($event);

// For a specific slot
$icsContent = CalendarService::generateICS($event, $slot);
$googleLink = CalendarService::generateGoogleCalendarLink($event, $slot);
```

### 2. Mail Service

**Location:** `src/MailService.php`

The MailService handles email sending for event confirmations using the existing SMTP configuration.

#### Methods:

- `sendHelperConfirmation($toEmail, $toName, $event, $slot, $icsContent, $googleCalendarLink)`: Sends a helper confirmation email
  - Includes HTML email body with event details
  - Attaches ICS file for calendar integration
  - Includes Google Calendar link
  - Returns: Boolean success status

- `sendEmail($toEmail, $subject, $htmlBody)`: Sends a simple HTML email
  - Used for general notifications
  - Returns: Boolean success status

#### Email Features:

- **HTML Template:** Professional HTML template with CSS styling
- **ICS Attachment:** Calendar file attached to email
- **Google Calendar Link:** One-click add to Google Calendar
- **XSS Prevention:** All user content properly escaped
- **Event Information:**
  - Event title and description
  - Shift time (slot or full event)
  - Location
  - Contact person

#### When Emails Are Sent:

1. **Helper Signup:** When a user signs up for a helper slot (not for general event participation)
2. **Waitlist Promotion:** When a confirmed user cancels and a waitlisted user is automatically promoted

**Note:** No emails are sent for general event participant signups (only for helper slot signups).

### 3. Event Status Updates (Pseudo-Cron)

**Location:** `includes/pseudo_cron.php` and `includes/models/Event.php`

Automatically updates event statuses based on current time.

#### How It Works:

1. **Rate Limiting:** Runs once every 5 minutes per session to avoid excessive database queries
2. **Integration:** Included in dashboard and events index pages
3. **Status Updates:**
   - `planned` → `open`: Events with future start times
   - `open`/`running`/`closed`/`planned` → `past`: Events where end_time has passed

#### Method:

```php
Event::updateEventStatuses()
```

Returns an array with:
- `planned_to_open`: Number of events changed to "open"
- `to_past`: Number of events changed to "past"

#### Integration:

Add to any PHP page after session start:

```php
AuthHandler::startSession();
require_once __DIR__ . '/includes/pseudo_cron.php';
```

### 4. Waitlist Management

**Location:** `includes/models/Event.php`

When a user with a confirmed slot cancels, the system automatically:

1. Promotes the first waitlisted user (oldest signup first)
2. Updates their status to "confirmed"
3. Sends them a confirmation email with calendar integration
4. Logs the promotion in event history

#### Modified Methods:

- `cancelSignup($signupId, $userId)`: Now returns promotion information
- `promoteWaitlistUser($eventId, $slotId)`: Private method for automatic promotion

## Database Schema

No schema changes were required. The existing event system tables support all new features.

## Configuration

Uses existing SMTP configuration from `config/config.php`:

```php
define('SMTP_HOST', 'smtp.ionos.de');
define('SMTP_PORT', 587);
define('SMTP_USER', 'mail@test.business-consulting.de');
define('SMTP_PASS', 'Test12345678.');
define('SMTP_FROM', 'mail@test.business-consulting.de');
```

## Testing

Test scripts are provided in the `tests/` directory:

1. **test_calendar_service.php**: Tests ICS generation and Google Calendar links
2. **test_mail_service.php**: Tests email body generation and XSS prevention
3. **test_event_status_update.php**: Tests status update logic (requires database)

Run tests:

```bash
php tests/test_calendar_service.php
php tests/test_mail_service.php
```

## API Integration

The email and calendar features are automatically integrated into the event signup API (`api/event_signup.php`).

### Signup Flow:

1. User signs up for a helper slot
2. Signup is created in database
3. If successful and status is "confirmed":
   - Event and slot details are retrieved
   - ICS file is generated
   - Google Calendar link is created
   - Confirmation email is sent

### Cancellation Flow:

1. User cancels their confirmed slot
2. Cancellation is recorded
3. Next waitlisted user is promoted (if any)
4. Promoted user receives confirmation email

## Security Considerations

1. **XSS Prevention:** All user-provided content is properly escaped using `htmlspecialchars()`
2. **Error Handling:** Email sending failures don't break the signup process
3. **Input Validation:** Event and user data validated before processing
4. **Rate Limiting:** Status updates limited to once every 5 minutes per session

## Browser Compatibility

- **ICS Files:** Compatible with all major calendar applications:
  - Google Calendar
  - Microsoft Outlook
  - Apple Calendar
  - Mozilla Thunderbird
  - And more

- **Email:** HTML emails render correctly in all modern email clients

## Future Enhancements

Potential improvements for future versions:

1. Add support for recurring events
2. Email template customization through admin interface
3. SMS notifications in addition to email
4. Calendar sync via CalDAV
5. Reminder emails before event start
6. Separate registration_open_time field for more precise control

## Troubleshooting

### Emails Not Sending

1. Verify SMTP configuration in `config/config.php`
2. Check error logs for email sending errors
3. Ensure PHP mail() function is properly configured
4. Test SMTP connection manually

### ICS Files Not Opening

1. Verify the generated ICS content is valid
2. Check that the MIME type is set correctly (`text/calendar`)
3. Ensure special characters are properly escaped

### Status Updates Not Working

1. Verify pseudo_cron.php is included on pages
2. Check that session is started before including pseudo_cron
3. Review error logs for database connection issues
4. Ensure 5-minute interval has passed since last check

## Code Example: Complete Flow

```php
// In a page where you want status updates
require_once __DIR__ . '/includes/handlers/AuthHandler.php';
AuthHandler::startSession();
require_once __DIR__ . '/includes/pseudo_cron.php';

// In the signup API
require_once __DIR__ . '/../src/CalendarService.php';
require_once __DIR__ . '/../src/MailService.php';

// After successful slot signup
$icsContent = CalendarService::generateICS($event, $slot);
$googleLink = CalendarService::generateGoogleCalendarLink($event, $slot);

MailService::sendHelperConfirmation(
    $userEmail,
    $userName,
    $event,
    $slot,
    $icsContent,
    $googleLink
);
```

## Support

For issues or questions about these features, please refer to:
- Event.php model documentation
- CalendarService.php class documentation
- MailService.php class documentation
- Test files for usage examples
