<?php
/**
 * API: Send Invitation
 * Generates invitation link for new users
 * Required permissions: admin, board, or alumni_board
 */

require_once __DIR__ . '/../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../src/MailService.php';

AuthHandler::startSession();

// Set JSON response header
header('Content-Type: application/json');

// Check authentication and permission
if (!AuthHandler::isAuthenticated() || !AuthHandler::hasPermission('board')) {
    echo json_encode([
        'success' => false,
        'message' => 'Nicht autorisiert. Nur Vorstände und Administratoren können Einladungen erstellen.'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Anfrage'
    ]);
    exit;
}

// Verify CSRF token
CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');

// Get POST data
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'member';
$sendMail = isset($_POST['send_mail']) && $_POST['send_mail'] == '1';

// Validate input
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige E-Mail-Adresse'
    ]);
    exit;
}

if (!in_array($role, ['member', 'alumni', 'manager', 'alumni_board', 'board', 'admin'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Rolle'
    ]);
    exit;
}

// Check if user already exists
$db = Database::getUserDB();
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Ein Benutzer mit dieser E-Mail-Adresse existiert bereits'
    ]);
    exit;
}

// Check if there's already an open invitation for this email
$stmt = $db->prepare("SELECT id FROM invitation_tokens WHERE email = ? AND used_at IS NULL AND expires_at > NOW()");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode([
        'success' => false,
        'message' => 'Es existiert bereits eine offene Einladung für diese E-Mail-Adresse'
    ]);
    exit;
}

// Generate invitation token
$token = AuthHandler::generateInvitationToken($email, $role, $_SESSION['user_id']);

// Build invitation link
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$invitationLink = $protocol . '://' . $host . '/pages/auth/register.php?token=' . $token;

// Check if we should send email
if ($sendMail) {
    // Send invitation email
    $mailSent = MailService::sendInvitation($email, $token, $role);
    
    if ($mailSent) {
        // Return success response with message about sent email
        echo json_encode([
            'success' => true,
            'message' => 'Einladung per E-Mail versendet.',
            'email' => $email,
            'role' => $role,
            'link' => $invitationLink
        ]);
    } else {
        // Email failed, but still return link
        echo json_encode([
            'success' => true,
            'message' => 'Link generiert, aber E-Mail konnte nicht versendet werden. Bitte Link manuell teilen.',
            'link' => $invitationLink,
            'email' => $email,
            'role' => $role
        ]);
    }
} else {
    // Return success response with just the link
    echo json_encode([
        'success' => true,
        'link' => $invitationLink,
        'message' => 'Link generiert.',
        'email' => $email,
        'role' => $role
    ]);
}
