<?php
/**
 * Generate sample HTML emails for sendProjectApplicationStatus
 * Run with: php tests/generate_project_status_email_samples.php
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Generating sample HTML emails for project application status...\n\n";

// Use reflection to test private methods
$reflectionClass = new ReflectionClass('MailService');
$acceptedMethod = $reflectionClass->getMethod('buildProjectApplicationAcceptedBody');
$acceptedMethod->setAccessible(true);

$rejectedMethod = $reflectionClass->getMethod('buildProjectApplicationRejectedBody');
$rejectedMethod->setAccessible(true);

// Test data
$testProjectTitle = 'Web Development Project - Neues IBC Intranet';
$testClientData = [
    'name' => 'Max Mustermann GmbH',
    'contact' => 'max.mustermann@example.com, Tel: +49 123 456789'
];

// Generate acceptance email with client data
echo "Generating acceptance email with client data...\n";
$acceptedEmailBody = $acceptedMethod->invoke(null, $testProjectTitle, $testClientData);
file_put_contents(__DIR__ . '/sample_project_acceptance_with_client.html', $acceptedEmailBody);
echo "✓ Saved to: tests/sample_project_acceptance_with_client.html\n";

// Generate acceptance email without client data
echo "Generating acceptance email without client data...\n";
$acceptedEmailBodyNoClient = $acceptedMethod->invoke(null, $testProjectTitle, null);
file_put_contents(__DIR__ . '/sample_project_acceptance_no_client.html', $acceptedEmailBodyNoClient);
echo "✓ Saved to: tests/sample_project_acceptance_no_client.html\n";

// Generate rejection email
echo "Generating rejection email...\n";
$rejectedEmailBody = $rejectedMethod->invoke(null, $testProjectTitle);
file_put_contents(__DIR__ . '/sample_project_rejection.html', $rejectedEmailBody);
echo "✓ Saved to: tests/sample_project_rejection.html\n";

echo "\n=== Sample HTML files generated ===\n";
echo "You can open these files in a browser to preview the emails.\n";
