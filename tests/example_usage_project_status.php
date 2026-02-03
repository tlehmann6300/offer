<?php
/**
 * Example usage of MailService::sendProjectApplicationStatus
 * 
 * This file demonstrates how to use the new sendProjectApplicationStatus method.
 * DO NOT run this file unless you want to send actual test emails.
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Example usage of MailService::sendProjectApplicationStatus\n\n";

// Example 1: Send acceptance email with client data
echo "Example 1: Sending acceptance email with client data\n";
echo "-------------------------------------------------------\n";
$userEmail = 'user@example.com';
$projectTitle = 'Web Development Project - Neues IBC Intranet';
$status = 'accepted';
$clientData = [
    'name' => 'Max Mustermann GmbH',
    'contact' => 'max.mustermann@example.com, Tel: +49 123 456789'
];

echo "Code:\n";
echo "\$result = MailService::sendProjectApplicationStatus(\n";
echo "    '{$userEmail}',\n";
echo "    '{$projectTitle}',\n";
echo "    '{$status}',\n";
echo "    [\n";
echo "        'name' => '{$clientData['name']}',\n";
echo "        'contact' => '{$clientData['contact']}'\n";
echo "    ]\n";
echo ");\n\n";

// Uncomment to actually send:
// $result = MailService::sendProjectApplicationStatus($userEmail, $projectTitle, $status, $clientData);
// echo "Result: " . ($result ? "✓ Email sent successfully" : "✗ Email sending failed") . "\n\n";

// Example 2: Send acceptance email without client data
echo "Example 2: Sending acceptance email without client data\n";
echo "-------------------------------------------------------\n";
echo "Code:\n";
echo "\$result = MailService::sendProjectApplicationStatus(\n";
echo "    'user@example.com',\n";
echo "    'Mobile App Development',\n";
echo "    'accepted'\n";
echo ");\n\n";

// Uncomment to actually send:
// $result = MailService::sendProjectApplicationStatus('user@example.com', 'Mobile App Development', 'accepted');
// echo "Result: " . ($result ? "✓ Email sent successfully" : "✗ Email sending failed") . "\n\n";

// Example 3: Send rejection email
echo "Example 3: Sending rejection email\n";
echo "-------------------------------------------------------\n";
echo "Code:\n";
echo "\$result = MailService::sendProjectApplicationStatus(\n";
echo "    'user@example.com',\n";
echo "    'Data Analysis Project',\n";
echo "    'rejected'\n";
echo ");\n\n";

// Uncomment to actually send:
// $result = MailService::sendProjectApplicationStatus('user@example.com', 'Data Analysis Project', 'rejected');
// echo "Result: " . ($result ? "✓ Email sent successfully" : "✗ Email sending failed") . "\n\n";

echo "Notes:\n";
echo "------\n";
echo "- The method returns true on success, false on failure\n";
echo "- For acceptance emails, clientData is optional but recommended\n";
echo "- Client data should contain 'name' and 'contact' keys\n";
echo "- Rejection emails automatically exclude client data\n";
echo "- All user inputs are automatically escaped to prevent XSS\n";
echo "- Emails use the IBC corporate design template\n";
echo "- A confidentiality notice is included in all acceptance emails\n";
