<?php
/**
 * Test Script: Invoice Mail with Attachment
 * Tests MailService::send() with invoice attachment
 */

// Load composer autoloader for PHPMailer first (before MailService needs it)
require_once __DIR__ . '/vendor/autoload.php';

// Load MailService (which will load config.php internally)
require_once __DIR__ . '/src/MailService.php';

try {
    // Step 1: Create dummy text file test_receipt.txt with content 'This is a test receipt'
    $tempFilePath = __DIR__ . '/test_receipt.txt';
    $fileContent = "This is a test receipt";
    
    // Write content to temporary file
    if (file_put_contents($tempFilePath, $fileContent) === false) {
        throw new Exception("Failed to create test_receipt.txt file");
    }
    
    // Step 2: Send email with attachment using MailService::send()
    $result = MailService::send(
        'tlehmann630@gmail.com',
        'Test: Invoice Attachment',
        'Please check attachment.',
        [$tempFilePath]
    );
    
    // Step 3: Cleanup - delete test_receipt.txt after sending
    if (file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    
    // Step 4: Print feedback
    if ($result) {
        echo "Email sent. Check inbox for attachment.\n";
    } else {
        echo "Error: Failed to send email.\n";
    }
    
} catch (Exception $e) {
    // Handle errors
    echo "Error: " . $e->getMessage() . "\n";
    
    // Cleanup on error
    if (isset($tempFilePath) && file_exists($tempFilePath)) {
        unlink($tempFilePath);
    }
    
    exit(1);
}
