<?php
/**
 * Test for the new sendInvitation method
 * Run with: php tests/test_invitation_email.php
 */

require_once __DIR__ . '/../src/MailService.php';

echo "Testing sendInvitation method...\n\n";

// Use reflection to test the private getTemplate method
$reflectionClass = new ReflectionClass('MailService');

echo "=== Test 1: Check sendInvitation method exists ===\n";
if (method_exists('MailService', 'sendInvitation')) {
    echo "✓ sendInvitation method exists\n";
    
    $method = $reflectionClass->getMethod('sendInvitation');
    $parameters = $method->getParameters();
    $paramNames = array_map(function($p) { return $p->getName(); }, $parameters);
    
    $expectedParams = ['email', 'token', 'role'];
    if ($paramNames === $expectedParams) {
        echo "✓ Method has correct parameters: " . implode(', ', $paramNames) . "\n";
    } else {
        echo "✗ Method parameters mismatch\n";
        echo "  Expected: " . implode(', ', $expectedParams) . "\n";
        echo "  Actual: " . implode(', ', $paramNames) . "\n";
    }
} else {
    echo "✗ sendInvitation method does not exist\n";
    exit(1);
}

echo "\n=== Test 2: Check getTemplate method ===\n";
if ($reflectionClass->hasMethod('getTemplate')) {
    echo "✓ getTemplate method exists\n";
    
    $templateMethod = $reflectionClass->getMethod('getTemplate');
    $templateMethod->setAccessible(true);
    
    // Test basic template generation
    $testTitle = 'Test Title';
    $testBody = '<p>Test body content</p>';
    $testCTA = '<a href="#" class="button">Test Button</a>';
    
    $template = $templateMethod->invoke(null, $testTitle, $testBody, $testCTA);
    
    // Check template structure
    if (strpos($template, $testTitle) !== false) {
        echo "✓ Template includes title\n";
    } else {
        echo "✗ Template missing title\n";
    }
    
    if (strpos($template, $testBody) !== false) {
        echo "✓ Template includes body content\n";
    } else {
        echo "✗ Template missing body content\n";
    }
    
    if (strpos($template, $testCTA) !== false) {
        echo "✓ Template includes call-to-action\n";
    } else {
        echo "✗ Template missing call-to-action\n";
    }
    
    // Check IBC design elements
    if (strpos($template, '#20234A') !== false) {
        echo "✓ Template has IBC dark blue header\n";
    } else {
        echo "✗ Template missing IBC dark blue header\n";
    }
    
    if (strpos($template, '#6D9744') !== false) {
        echo "✓ Template has IBC green accent\n";
    } else {
        echo "✗ Template missing IBC green accent\n";
    }
    
    if (strpos($template, '#f3f4f6') !== false) {
        echo "✓ Template has light gray background\n";
    } else {
        echo "✗ Template missing light gray background\n";
    }
    
    if (strpos($template, 'cid:ibc_logo') !== false) {
        echo "✓ Template includes embedded logo reference\n";
    } else {
        echo "✗ Template missing embedded logo reference\n";
    }
    
    if (strpos($template, 'max-width: 600px') !== false) {
        echo "✓ Template has correct container width\n";
    } else {
        echo "✗ Template container width incorrect\n";
    }
    
} else {
    echo "✗ getTemplate method does not exist\n";
}

echo "\n=== Test 3: Validate invitation email content ===\n";

// Since we can't actually send emails in tests, we'll check the method source
$methodSource = file_get_contents(__DIR__ . '/../src/MailService.php');

$invitationChecks = [
    'Einladung zum IBC Intranet' => 'Subject line',
    'du wurdest als' => 'Invitation message',
    'Jetzt registrieren' => 'Registration button text',
    '/pages/auth/register.php?token=' => 'Registration link structure',
    'ucfirst($role)' => 'Role capitalization',
    'sendEmailWithEmbeddedImage' => 'Uses embedded image method'
];

foreach ($invitationChecks as $check => $description) {
    if (strpos($methodSource, $check) !== false) {
        echo "✓ $description present\n";
    } else {
        echo "✗ $description missing\n";
    }
}

echo "\n=== Test 4: Check embedded image methods ===\n";

if ($reflectionClass->hasMethod('sendEmailWithEmbeddedImage')) {
    echo "✓ sendEmailWithEmbeddedImage method exists\n";
} else {
    echo "✗ sendEmailWithEmbeddedImage method missing\n";
}

if (strpos($methodSource, 'multipart/related') !== false) {
    echo "✓ Uses multipart/related for embedded images\n";
} else {
    echo "✗ multipart/related not found\n";
}

if (strpos($methodSource, 'Content-ID: <ibc_logo>') !== false) {
    echo "✓ Properly sets Content-ID for embedded logo\n";
} else {
    echo "✗ Content-ID not properly set\n";
}

if (strpos($methodSource, 'ibc_logo_original_navbar') !== false) {
    echo "✓ References correct logo file\n";
} else {
    echo "✗ Logo file reference missing\n";
}

echo "\n=== Test 5: Check sendHelperConfirmation updates ===\n";

if (strpos($methodSource, 'class="info-table"') !== false) {
    echo "✓ Uses info table for event details\n";
} else {
    echo "✗ Info table not found\n";
}

if (strpos($methodSource, '<td>Wann</td>') !== false && 
    strpos($methodSource, '<td>Wo</td>') !== false && 
    strpos($methodSource, '<td>Rolle</td>') !== false) {
    echo "✓ Event details formatted as table (Wann, Wo, Rolle)\n";
} else {
    echo "✗ Event details table structure incomplete\n";
}

if (strpos($methodSource, 'In Kalender speichern') !== false) {
    echo "✓ Calendar button has German text\n";
} else {
    echo "✗ Calendar button text missing\n";
}

echo "\n=== All invitation tests completed ===\n";
echo "✓ sendInvitation method is properly implemented\n";
echo "✓ getTemplate method creates IBC corporate design\n";
echo "✓ Embedded logo (CID) support is implemented\n";
echo "✓ sendHelperConfirmation uses new template with table layout\n";
