<?php
/**
 * Generate sample HTML files for email templates
 * This creates HTML files that can be opened in a browser to preview the email designs
 */

require_once __DIR__ . '/../src/MailService.php';

// Create samples directory
$samplesDir = __DIR__ . '/../samples';
if (!is_dir($samplesDir)) {
    mkdir($samplesDir, 0755, true);
}

// Use reflection to access private methods
$reflectionClass = new ReflectionClass('MailService');
$buildMethod = $reflectionClass->getMethod('buildHelperConfirmationBody');
$buildMethod->setAccessible(true);

// Sample 1: Helper Confirmation Email
echo "Generating sample helper confirmation email...\n";

$testEvent = [
    'id' => 123,
    'title' => 'Sommerfest 2024',
    'description' => 'Jährliches Sommerfest mit Live-Musik, Essen und Getränken für alle IBC-Mitglieder',
    'location' => 'Hauptcampus, Gebäude H, Raum 101',
    'start_time' => '2024-07-15 10:00:00',
    'end_time' => '2024-07-15 18:00:00',
    'contact_person' => 'Max Mustermann (max.mustermann@ibc.example)'
];

$testSlot = [
    'id' => 456,
    'start_time' => '2024-07-15 14:00:00',
    'end_time' => '2024-07-15 16:00:00'
];

$googleCalendarLink = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=Sommerfest+2024';

$helperConfirmationHtml = $buildMethod->invoke(null, 'Anna Schmidt', $testEvent, $testSlot, $googleCalendarLink);

// Replace cid:ibc_logo with an actual path for preview
$helperConfirmationHtml = str_replace(
    'src="cid:ibc_logo"',
    'src="../assets/img/ibc_logo_original_navbar.png"',
    $helperConfirmationHtml
);

file_put_contents($samplesDir . '/helper_confirmation_email.html', $helperConfirmationHtml);
echo "✓ Created: samples/helper_confirmation_email.html\n";

// Sample 2: Invitation Email
echo "Generating sample invitation email...\n";

// Access the getTemplate method
$templateMethod = $reflectionClass->getMethod('getTemplate');
$templateMethod->setAccessible(true);

$invitationBody = '<p class="email-text">Hallo,</p>
<p class="email-text">du wurdest als <strong>Helfer</strong> zum IBC Intranet eingeladen.</p>
<p class="email-text">Um dein Konto zu erstellen und Zugang zum System zu erhalten, klicke bitte auf den folgenden Button:</p>';

$invitationCTA = '<a href="https://ibc-intranet.example/pages/auth/register.php?token=sample-token-123" class="button">Jetzt registrieren</a>';

$invitationBody .= '<p class="email-text" style="margin-top: 20px; font-size: 14px; color: #6b7280;">Dieser Einladungslink ist nur einmal verwendbar. Falls du Probleme beim Registrieren hast, wende dich bitte an den Administrator.</p>';

$invitationHtml = $templateMethod->invoke(null, 'Einladung zum IBC Intranet', $invitationBody, $invitationCTA);

// Replace cid:ibc_logo with an actual path for preview
$invitationHtml = str_replace(
    'src="cid:ibc_logo"',
    'src="../assets/img/ibc_logo_original_navbar.png"',
    $invitationHtml
);

file_put_contents($samplesDir . '/invitation_email.html', $invitationHtml);
echo "✓ Created: samples/invitation_email.html\n";

// Sample 3: Helper Confirmation (Full Event, No Slot)
echo "Generating sample full event confirmation email...\n";

$fullEventHtml = $buildMethod->invoke(null, 'Peter Müller', $testEvent, null, $googleCalendarLink);

// Replace cid:ibc_logo with an actual path for preview
$fullEventHtml = str_replace(
    'src="cid:ibc_logo"',
    'src="../assets/img/ibc_logo_original_navbar.png"',
    $fullEventHtml
);

file_put_contents($samplesDir . '/full_event_confirmation_email.html', $fullEventHtml);
echo "✓ Created: samples/full_event_confirmation_email.html\n";

echo "\n=== Sample HTML files created successfully ===\n";
echo "You can open these files in a browser to preview the email designs:\n";
echo "- samples/helper_confirmation_email.html\n";
echo "- samples/invitation_email.html\n";
echo "- samples/full_event_confirmation_email.html\n";
