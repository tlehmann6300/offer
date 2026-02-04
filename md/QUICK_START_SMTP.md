# Quick Start: Testing SMTP Configuration

## Prerequisites
Make sure your `.env` file contains these variables:
```
SMTP_HOST=your-smtp-server.com
SMTP_PORT=587
SMTP_USER=your-username@example.com
SMTP_PASS="your-password"
SMTP_FROM_EMAIL=noreply@example.com
SMTP_FROM_NAME="Your App Name"
```

## Testing the Configuration

### Option 1: Command Line Test Script
```bash
# Test with your email address
php test_smtp.php your-email@example.com
```

This will:
- Show the current SMTP configuration
- Send a test email with full debug output
- Display any connection errors

### Option 2: Programmatic Test
```php
require_once 'src/MailService.php';

$result = MailService::sendTestMail('your-email@example.com');
if ($result) {
    echo "Email sent successfully!";
} else {
    echo "Failed to send email. Check error logs.";
}
```

## Using in Your Code

### Sending Simple Emails
```php
$subject = "Hello World";
$htmlBody = "<h1>Test Email</h1><p>This is a test.</p>";
MailService::sendEmail('recipient@example.com', $subject, $htmlBody);
```

### Sending Emails with Embedded Logo
```php
$htmlBody = self::getTemplate('Email Title', '<p>Content here</p>');
MailService::sendEmailWithEmbeddedImage('recipient@example.com', 'Subject', $htmlBody);
```

## Troubleshooting

### Common Issues

1. **Authentication Failed**
   - Verify SMTP_USER and SMTP_PASS are correct
   - Check if your SMTP server requires app-specific passwords

2. **Connection Timeout**
   - Verify SMTP_HOST and SMTP_PORT are correct
   - Check firewall settings

3. **TLS/SSL Errors**
   - Most modern SMTP servers use port 587 with STARTTLS
   - Some use port 465 with SSL (requires changing SMTPSecure setting)

### Enable Debug Output
Use `sendTestMail()` to see full SMTP connection logs:
```php
MailService::sendTestMail('test@example.com');
```

This will show:
- Connection attempts
- Authentication process
- Message sending steps
- Any errors or warnings

## Security Best Practices

1. Never commit `.env` file with real credentials
2. Use strong passwords for SMTP accounts
3. Limit SMTP account permissions to sending only
4. Monitor email logs for suspicious activity
5. Rotate SMTP credentials regularly

## Support

For detailed information, see [SMTP_CONFIGURATION.md](SMTP_CONFIGURATION.md)
