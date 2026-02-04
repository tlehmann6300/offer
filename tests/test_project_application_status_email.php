<?php
/**
 * Unit test for MailService::sendProjectApplicationStatus
 * Run with: php tests/test_project_application_status_email.php
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Testing MailService::sendProjectApplicationStatus...\n\n";

// Use reflection to test private methods
$reflectionClass = new ReflectionClass('MailService');
$acceptedMethod = $reflectionClass->getMethod('buildProjectApplicationAcceptedBody');
$acceptedMethod->setAccessible(true);

$rejectedMethod = $reflectionClass->getMethod('buildProjectApplicationRejectedBody');
$rejectedMethod->setAccessible(true);

// Test data
$testProjectTitle = 'Web Development Project';
$testProjectId = 123; // Test project ID
$testClientData = [
    'name' => 'Max Mustermann GmbH',
    'contact' => 'max.mustermann@example.com, Tel: +49 123 456789'
];

echo "=== Test 1: Acceptance email with client data ===\n";
$acceptedEmailBody = $acceptedMethod->invoke(null, $testProjectTitle, $testProjectId, $testClientData);

if (!empty($acceptedEmailBody)) {
    echo "✓ Acceptance email body generated\n";
} else {
    echo "✗ Failed to generate acceptance email body\n";
}

echo "\n=== Test 2: Acceptance email content validation ===\n";

// Check if project title is included
if (strpos($acceptedEmailBody, 'Web Development Project') !== false) {
    echo "✓ Project title is included\n";
} else {
    echo "✗ Project title is missing\n";
}

// Check if "angenommen" (accepted) is mentioned
if (strpos($acceptedEmailBody, 'angenommen') !== false) {
    echo "✓ Acceptance message is included\n";
} else {
    echo "✗ Acceptance message is missing\n";
}

// Check if client name is included
if (strpos($acceptedEmailBody, 'Max Mustermann GmbH') !== false) {
    echo "✓ Client name is included\n";
} else {
    echo "✗ Client name is missing\n";
}

// Check if client contact is included
if (strpos($acceptedEmailBody, 'max.mustermann@example.com') !== false) {
    echo "✓ Client contact is included\n";
} else {
    echo "✗ Client contact is missing\n";
}

// Check if confidentiality notice is included
if (strpos($acceptedEmailBody, 'Vertraulichkeit') !== false) {
    echo "✓ Confidentiality notice is included\n";
} else {
    echo "✗ Confidentiality notice is missing\n";
}

// Check if "vertraulich" keyword is present
if (strpos($acceptedEmailBody, 'vertraulich') !== false) {
    echo "✓ Confidentiality keyword is present\n";
} else {
    echo "✗ Confidentiality keyword is missing\n";
}

// Check for info table structure
if (strpos($acceptedEmailBody, '<table class="info-table">') !== false) {
    echo "✓ Info table structure present\n";
} else {
    echo "✗ Info table structure missing\n";
}

echo "\n=== Test 3: Rejection email ===\n";
$rejectedEmailBody = $rejectedMethod->invoke(null, $testProjectTitle);

if (!empty($rejectedEmailBody)) {
    echo "✓ Rejection email body generated\n";
} else {
    echo "✗ Failed to generate rejection email body\n";
}

echo "\n=== Test 4: Rejection email content validation ===\n";

// Check if project title is included
if (strpos($rejectedEmailBody, 'Web Development Project') !== false) {
    echo "✓ Project title is included\n";
} else {
    echo "✗ Project title is missing\n";
}

// Check if friendly rejection message is present
if (strpos($rejectedEmailBody, 'Leider') !== false || strpos($rejectedEmailBody, 'andere Bewerber') !== false) {
    echo "✓ Friendly rejection message is included\n";
} else {
    echo "✗ Friendly rejection message is missing\n";
}

// Check for encouraging message
if (strpos($rejectedEmailBody, 'ermutigen') !== false || strpos($rejectedEmailBody, 'Erfolg') !== false) {
    echo "✓ Encouraging message is included\n";
} else {
    echo "✗ Encouraging message is missing\n";
}

// Check that client data is NOT in rejection email
if (strpos($rejectedEmailBody, 'Max Mustermann') === false) {
    echo "✓ Client data correctly NOT included in rejection\n";
} else {
    echo "✗ Client data incorrectly included in rejection\n";
}

// Check that confidentiality notice is NOT in rejection email
if (strpos($rejectedEmailBody, 'Vertraulichkeit') === false) {
    echo "✓ Confidentiality notice correctly NOT included in rejection\n";
} else {
    echo "✗ Confidentiality notice incorrectly included in rejection\n";
}

echo "\n=== Test 5: HTML structure validation ===\n";

// Check for proper HTML structure in acceptance email
$hasHeader = strpos($acceptedEmailBody, '<div class="email-header">') !== false;
$hasContent = strpos($acceptedEmailBody, '<div class="email-body">') !== false;
$hasFooter = strpos($acceptedEmailBody, '<div class="email-footer">') !== false;

if ($hasHeader && $hasContent && $hasFooter) {
    echo "✓ Acceptance email has proper structure\n";
} else {
    echo "✗ Acceptance email structure is incomplete\n";
}

// Check for proper HTML structure in rejection email
$hasHeader = strpos($rejectedEmailBody, '<div class="email-header">') !== false;
$hasContent = strpos($rejectedEmailBody, '<div class="email-body">') !== false;
$hasFooter = strpos($rejectedEmailBody, '<div class="email-footer">') !== false;

if ($hasHeader && $hasContent && $hasFooter) {
    echo "✓ Rejection email has proper structure\n";
} else {
    echo "✗ Rejection email structure is incomplete\n";
}

// Check for IBC corporate design colors
if (strpos($acceptedEmailBody, '#20234A') !== false && strpos($acceptedEmailBody, '#6D9744') !== false) {
    echo "✓ IBC corporate colors present in acceptance email\n";
} else {
    echo "✗ IBC corporate colors missing in acceptance email\n";
}

if (strpos($rejectedEmailBody, '#20234A') !== false && strpos($rejectedEmailBody, '#6D9744') !== false) {
    echo "✓ IBC corporate colors present in rejection email\n";
} else {
    echo "✗ IBC corporate colors missing in rejection email\n";
}

echo "\n=== Test 6: XSS prevention ===\n";

$xssProjectTitle = '<script>alert("XSS")</script>Malicious Project';
$xssClientData = [
    'name' => '<script>alert("XSS")</script>Evil Corp',
    'contact' => '<img src=x onerror=alert("XSS")>evil@example.com'
];

$xssAcceptedBody = $acceptedMethod->invoke(null, $xssProjectTitle, $testProjectId, $xssClientData);

if (strpos($xssAcceptedBody, '<script>') === false && strpos($xssAcceptedBody, '&lt;script&gt;') !== false) {
    echo "✓ XSS in project title properly escaped\n";
} else {
    echo "✗ XSS in project title not properly escaped\n";
}

if (strpos($xssAcceptedBody, '<img src=x') === false && strpos($xssAcceptedBody, '&lt;img') !== false) {
    echo "✓ XSS in client data properly escaped\n";
} else {
    echo "✗ XSS in client data not properly escaped\n";
}

$xssRejectedBody = $rejectedMethod->invoke(null, $xssProjectTitle);

if (strpos($xssRejectedBody, '<script>') === false && strpos($xssRejectedBody, '&lt;script&gt;') !== false) {
    echo "✓ XSS in rejection email properly escaped\n";
} else {
    echo "✗ XSS in rejection email not properly escaped\n";
}

echo "\n=== Test 7: Acceptance email without client data ===\n";

$acceptedEmailBodyNoClient = $acceptedMethod->invoke(null, $testProjectTitle, $testProjectId, null);

if (!empty($acceptedEmailBodyNoClient)) {
    echo "✓ Acceptance email without client data generated\n";
} else {
    echo "✗ Failed to generate acceptance email without client data\n";
}

// Should still have acceptance message but no client table
if (strpos($acceptedEmailBodyNoClient, 'angenommen') !== false) {
    echo "✓ Acceptance message present without client data\n";
} else {
    echo "✗ Acceptance message missing without client data\n";
}

// Confidentiality notice should still be present
if (strpos($acceptedEmailBodyNoClient, 'Vertraulichkeit') !== false) {
    echo "✓ Confidentiality notice present even without client data\n";
} else {
    echo "✗ Confidentiality notice missing without client data\n";
}

echo "\n=== Test 8: Public method sendProjectApplicationStatus ===\n";

// Test that the public method exists with correct signature
echo "Testing public API method:\n";

try {
    $reflectionPublic = new ReflectionMethod('MailService', 'sendProjectApplicationStatus');
    if ($reflectionPublic->isPublic() && $reflectionPublic->isStatic()) {
        echo "✓ sendProjectApplicationStatus is public and static\n";
    } else {
        echo "✗ sendProjectApplicationStatus is not properly defined\n";
    }
} catch (Exception $e) {
    echo "✗ sendProjectApplicationStatus method not found: " . $e->getMessage() . "\n";
}

// Check method parameters
$params = $reflectionPublic->getParameters();
if (count($params) === 5) {
    echo "✓ Method has correct number of parameters (5)\n";
    
    if ($params[0]->getName() === 'userEmail') {
        echo "✓ First parameter is 'userEmail'\n";
    } else {
        echo "✗ First parameter name incorrect\n";
    }
    
    if ($params[1]->getName() === 'projectTitle') {
        echo "✓ Second parameter is 'projectTitle'\n";
    } else {
        echo "✗ Second parameter name incorrect\n";
    }
    
    if ($params[2]->getName() === 'status') {
        echo "✓ Third parameter is 'status'\n";
    } else {
        echo "✗ Third parameter name incorrect\n";
    }
    
    if ($params[3]->getName() === 'projectId') {
        echo "✓ Fourth parameter is 'projectId'\n";
    } else {
        echo "✗ Fourth parameter name incorrect\n";
    }
    
    if ($params[4]->getName() === 'clientData' && $params[4]->isOptional()) {
        echo "✓ Fifth parameter is 'clientData' and is optional\n";
    } else {
        echo "✗ Fifth parameter incorrect or not optional\n";
    }
} else {
    echo "✗ Method has incorrect number of parameters\n";
}

echo "\n=== Test 9: Verify project link and button text ===\n";

// Test that the acceptance email contains the correct project link
if (strpos($acceptedEmailBody, '/pages/projects/view.php?id=' . $testProjectId) !== false) {
    echo "✓ Acceptance email contains correct project view link\n";
} else {
    echo "✗ Acceptance email does not contain correct project view link\n";
}

// Test that the button text is "Zum Projekt"
if (strpos($acceptedEmailBody, '>Zum Projekt</a>') !== false) {
    echo "✓ Button text is 'Zum Projekt'\n";
} else {
    echo "✗ Button text is not 'Zum Projekt'\n";
}

// Test that the old link to manage.php is NOT present
if (strpos($acceptedEmailBody, '/pages/projects/manage.php') === false) {
    echo "✓ Old manage.php link correctly removed\n";
} else {
    echo "✗ Old manage.php link still present\n";
}

echo "\n=== All tests completed ===\n";
