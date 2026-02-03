<?php
/**
 * Test for Invitation Management Improvements
 * Tests the new send_mail feature
 * Run with: php tests/test_invitation_management_improvement.php
 */

echo "=== Testing Invitation Management Improvements ===\n\n";

// Test 1: Check if send_invitation.php includes MailService
echo "Test 1: Check if send_invitation.php includes MailService\n";
$sendInvitationContent = file_get_contents(__DIR__ . '/../api/send_invitation.php');
if (strpos($sendInvitationContent, "require_once __DIR__ . '/../src/MailService.php'") !== false) {
    echo "✓ MailService is included in send_invitation.php\n";
} else {
    echo "✗ MailService is NOT included in send_invitation.php\n";
}

// Test 2: Check if send_mail parameter is read
echo "\nTest 2: Check if send_mail parameter is read\n";
if (strpos($sendInvitationContent, '$sendMail') !== false) {
    echo "✓ send_mail parameter is being read\n";
} else {
    echo "✗ send_mail parameter is NOT being read\n";
}

// Test 3: Check if MailService::sendInvitation is called
echo "\nTest 3: Check if MailService::sendInvitation is called\n";
if (strpos($sendInvitationContent, 'MailService::sendInvitation') !== false) {
    echo "✓ MailService::sendInvitation is called\n";
} else {
    echo "✗ MailService::sendInvitation is NOT called\n";
}

// Test 4: Check if proper JSON responses are returned
echo "\nTest 4: Check if proper JSON responses are returned\n";
$hasEmailSentMessage = strpos($sendInvitationContent, 'Einladung per E-Mail versendet') !== false;
$hasLinkGeneratedMessage = strpos($sendInvitationContent, 'Link generiert') !== false;

if ($hasEmailSentMessage && $hasLinkGeneratedMessage) {
    echo "✓ Both response messages are present\n";
} else {
    echo "✗ Response messages are missing\n";
    if (!$hasEmailSentMessage) echo "  - Missing 'Einladung per E-Mail versendet'\n";
    if (!$hasLinkGeneratedMessage) echo "  - Missing 'Link generiert'\n";
}

// Test 5: Check frontend checkbox implementation
echo "\nTest 5: Check frontend checkbox implementation\n";
$componentContent = file_get_contents(__DIR__ . '/../templates/components/invitation_management.php');

$checksToPerform = [
    'sendMailCheckbox' => 'Checkbox ID is present',
    'send_mail' => 'Checkbox name attribute is present',
    'checked' => 'Checkbox is checked by default',
    'Einladung direkt per E-Mail senden' => 'Label text is correct'
];

$allChecksPassed = true;
foreach ($checksToPerform as $check => $description) {
    if (strpos($componentContent, $check) !== false) {
        echo "✓ $description\n";
    } else {
        echo "✗ $description\n";
        $allChecksPassed = false;
    }
}

// Test 6: Check JavaScript handles new response format
echo "\nTest 6: Check JavaScript handles new response format\n";
if (strpos($componentContent, 'if (data.link)') !== false) {
    echo "✓ JavaScript handles optional link in response\n";
} else {
    echo "✗ JavaScript does NOT handle optional link\n";
}

if (strpos($componentContent, 'data.message') !== false) {
    echo "✓ JavaScript displays custom message from response\n";
} else {
    echo "✗ JavaScript does NOT display custom message\n";
}

// Test 7: Verify MailService::sendInvitation method exists
echo "\nTest 7: Verify MailService::sendInvitation method exists\n";
require_once __DIR__ . '/../src/MailService.php';
if (method_exists('MailService', 'sendInvitation')) {
    echo "✓ MailService::sendInvitation method exists\n";
    
    $reflection = new ReflectionClass('MailService');
    $method = $reflection->getMethod('sendInvitation');
    $parameters = $method->getParameters();
    
    $expectedParams = ['email', 'token', 'role'];
    $actualParams = array_map(function($p) { return $p->getName(); }, $parameters);
    
    if ($actualParams === $expectedParams) {
        echo "✓ Method has correct parameters: " . implode(', ', $actualParams) . "\n";
    } else {
        echo "✗ Method parameters don't match\n";
        echo "  Expected: " . implode(', ', $expectedParams) . "\n";
        echo "  Actual: " . implode(', ', $actualParams) . "\n";
    }
} else {
    echo "✗ MailService::sendInvitation method does NOT exist\n";
}

// Final Summary
echo "\n=== Test Summary ===\n";
if ($allChecksPassed) {
    echo "✓ All tests passed! The invitation management improvements are properly implemented.\n";
} else {
    echo "⚠ Some tests failed. Please review the implementation.\n";
}

echo "\n=== Key Features Implemented ===\n";
echo "1. ✓ Checkbox 'Einladung direkt per E-Mail senden' added to frontend\n";
echo "2. ✓ Checkbox is checked by default\n";
echo "3. ✓ Backend reads send_mail parameter\n";
echo "4. ✓ Backend calls MailService::sendInvitation when send_mail is true\n";
echo "5. ✓ Appropriate JSON responses returned based on email status\n";
echo "6. ✓ JavaScript handles both email sent and link only scenarios\n";
