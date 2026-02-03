<?php
/**
 * Live SMTP Test Script
 * Tests PHPMailer configuration with verbose debugging output
 */

// Load PHPMailer via Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Manually load and parse .env file if $_ENV is empty
function loadEnvToEnvironment($path) {
    if (!file_exists($path)) {
        die('<h2 style="color: red;">Error: .env file not found at ' . htmlspecialchars($path) . '</h2>');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove inline comments (everything after # that's not in quotes)
            if (strpos($value, '#') !== false) {
                if (!((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                      (substr($value, 0, 1) === "'" && substr($value, -1) === "'"))) {
                    $value = trim(explode('#', $value)[0]);
                }
            }
            
            // Remove quotes if present
            if (strlen($value) >= 2) {
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            
            // Set to $_ENV
            $_ENV[$key] = $value;
        }
    }
}

// Load .env file
$envPath = __DIR__ . '/.env';
if (empty($_ENV['SMTP_HOST'])) {
    loadEnvToEnvironment($envPath);
}

// Verify required environment variables are loaded
$requiredVars = ['SMTP_HOST', 'SMTP_USER', 'SMTP_PASS', 'SMTP_PORT'];
foreach ($requiredVars as $var) {
    if (empty($_ENV[$var])) {
        die('<h2 style="color: red;">Error: Required environment variable ' . htmlspecialchars($var) . ' not found in .env file</h2>');
    }
}

// Initialize PHPMailer
$mail = new PHPMailer(true);

try {
    // Enable verbose SMTP debugging (2 = show client and server messages)
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';
    
    // Configure SMTP
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'];
    $mail->Password = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['SMTP_PORT'];
    
    // Set sender and recipient
    $mail->setFrom($_ENV['SMTP_USER'], 'SMTP Test');
    $mail->addAddress($_ENV['SMTP_USER']); // Send to myself
    
    // Email content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'SMTP Live Test - ' . date('Y-m-d H:i:s');
    $mail->Body = '<html>
    <body>
        <h2 style="color: green;">SMTP Test Successful!</h2>
        <p>This is a test email sent from test_mail_live.php</p>
        <p><strong>Configuration:</strong></p>
        <ul>
            <li>SMTP Host: ' . htmlspecialchars($_ENV['SMTP_HOST']) . '</li>
            <li>SMTP Port: ' . htmlspecialchars($_ENV['SMTP_PORT']) . '</li>
            <li>SMTP User: ' . htmlspecialchars($_ENV['SMTP_USER']) . '</li>
        </ul>
        <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
    </body>
    </html>';
    
    // Send the email
    $mail->send();
    
    // Success message
    echo '<h2 style="color: green;">✓ Email sent successfully!</h2>';
    echo '<p>Test email has been sent to: ' . htmlspecialchars($_ENV['SMTP_USER']) . '</p>';
    
} catch (Exception $e) {
    // Error message
    echo '<h2 style="color: red;">✗ Email sending failed!</h2>';
    echo '<p style="color: red;">Error: ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
}
