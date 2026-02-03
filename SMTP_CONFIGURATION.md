# MailService SMTP Configuration

## Overview
The MailService has been updated to use PHPMailer for SMTP email delivery instead of PHP's built-in mail() function. This provides more reliable email delivery and better debugging capabilities.

## Configuration

### Environment Variables
The following environment variables must be set in your `.env` file:

```
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=your-smtp-username@example.com
SMTP_PASS="your-password-here"
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME="Your Application Name"
```

### SMTP Settings Used
- **Protocol**: SMTP with STARTTLS encryption
- **Authentication**: Enabled (uses SMTP_USER and SMTP_PASS)
- **Port**: 587 (default for STARTTLS)
- **Character Encoding**: UTF-8
- **Transfer Encoding**: base64

## Key Changes

### 1. PHPMailer Integration
- Added PHPMailer library via Composer
- All email methods now use PHPMailer instead of PHP's mail() function
- Better error handling and debugging capabilities

### 2. New Methods

#### `createMailer($enableDebug = false)`
Private method that creates and configures a PHPMailer instance with SMTP settings.

```php
$mail = self::createMailer(); // Normal mode
$mail = self::createMailer(true); // Debug mode with SMTP output
```

#### `sendTestMail($toEmail)`
Public method to send a test email with SMTP debug output enabled. This is useful for testing the SMTP configuration.

```php
MailService::sendTestMail('test@example.com');
```

### 3. Updated Methods
All existing methods have been refactored to use PHPMailer:
- `sendEmailWithAttachment()` - Sends emails with ICS attachments
- `sendEmailWithEmbeddedImage()` - Sends emails with embedded logo
- `sendEmail()` - Sends simple HTML emails

## Testing

### Using the Test Script
A test script has been provided at `test_smtp.php`:

```bash
# Test with default email
php test_smtp.php

# Test with specific email
php test_smtp.php your-email@example.com
```

The test script will:
1. Display the current SMTP configuration
2. Send a test email with debug output enabled
3. Show the full SMTP connection log
4. Confirm success or failure

### Manual Testing
You can also test the SMTP configuration programmatically:

```php
require_once 'src/MailService.php';

// Send a test email
$result = MailService::sendTestMail('your-email@example.com');

if ($result) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send test email. Check error logs.";
}
```

## Debugging

### Enable Debug Output
When using `sendTestMail()`, debug output is automatically enabled with `SMTPDebug = 2`. This shows:
- Connection attempts
- Authentication steps
- Message sending process
- Any errors or warnings

### Check Error Logs
All email sending failures are logged using `error_log()`. Check your PHP error logs for detailed error messages.

## Security Notes

1. **Credentials**: SMTP credentials are stored in the `.env` file, which should never be committed to version control
2. **Encryption**: STARTTLS encryption is used to secure the connection to the SMTP server
3. **Authentication**: SMTP authentication is required for all email sending

## Migration from Old Code

The old code used PHP's built-in `mail()` function with manual MIME boundary construction. The new code:
- Uses PHPMailer's built-in methods for attachments and embedded images
- Provides better error handling
- Supports SMTP authentication and encryption
- Offers debugging capabilities
- Is more maintainable and reliable

All existing method signatures remain unchanged, so no changes are needed in code that calls the MailService methods.
