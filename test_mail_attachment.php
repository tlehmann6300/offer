<?php
/**
 * Test Script: Mail Attachment
 * Tests MailService::send() with file attachments
 */

// Load composer autoloader for PHPMailer first (before MailService needs it)
require_once __DIR__ . '/vendor/autoload.php';

// Load MailService (which will load config.php internally)
require_once __DIR__ . '/src/MailService.php';

try {
    // Step 1: Create a temporary text file with sample content
    $tempFilePath = __DIR__ . '/test_receipt.txt';
    $fileContent = "System Test Receipt\n";
    $fileContent .= "===================\n\n";
    $fileContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $fileContent .= "Test ID: " . uniqid() . "\n";
    $fileContent .= "Description: PHPMailer attachment upgrade test\n\n";
    $fileContent .= "This is a test file to verify that email attachments\n";
    $fileContent .= "are working correctly with PHPMailer.\n\n";
    $fileContent .= "If you received this file, the upgrade was successful!\n";
    
    // Write content to temporary file
    if (file_put_contents($tempFilePath, $fileContent) === false) {
        throw new Exception("Failed to create temporary file: {$tempFilePath}");
    }
    
    echo "✓ Created temporary file: test_receipt.txt\n";
    
    // Step 2: Send email with attachment using MailService::send()
    $toEmail = 'tlehmann630@gmail.com';
    $subject = 'System Test: Invoice Attachment';
    $body = '<html>
    <body>
        <h2>PHPMailer Attachment Test</h2>
        <p>If you see the attachment, the upgrade worked.</p>
        <p><strong>Test Details:</strong></p>
        <ul>
            <li>Date: ' . date('Y-m-d H:i:s') . '</li>
            <li>Attachment: test_receipt.txt</li>
            <li>Mail Service: PHPMailer</li>
        </ul>
        <p>Please check that the attachment file is present and readable.</p>
    </body>
    </html>';
    
    // Pass attachment as an array
    $attachments = [$tempFilePath];
    
    echo "Sending email to {$toEmail}...\n";
    
    // Call MailService::send()
    $result = MailService::send($toEmail, $subject, $body, $attachments);
    
    if ($result) {
        echo "✓ Email sent successfully!\n";
    } else {
        echo "✗ Failed to send email\n";
    }
    
    // Step 3: Cleanup - delete temporary file
    if (file_exists($tempFilePath)) {
        if (unlink($tempFilePath)) {
            echo "✓ Cleaned up temporary file\n";
        } else {
            echo "⚠ Warning: Could not delete temporary file: {$tempFilePath}\n";
        }
    }
    
    // Step 4: Output success message
    echo "\n";
    echo "Mail sent using PHPMailer. Check your inbox for the attachment.\n";
    
} catch (Exception $e) {
    // Handle errors
    echo "✗ Error: " . $e->getMessage() . "\n";
    
    // Cleanup on error
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        if (unlink($tempFilePath)) {
            echo "✓ Cleaned up temporary file after error\n";
        } else {
            echo "⚠ Warning: Could not delete temporary file after error: {$tempFilePath}\n";
        }
    }
    
    exit(1);
}
