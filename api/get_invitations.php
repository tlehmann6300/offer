<?php
/**
 * API: Get Invitations
 * Lists all open invitations
 * Required permissions: admin, board, or alumni_board
 */

require_once __DIR__ . '/../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../includes/database.php';

AuthHandler::startSession();

// Set JSON response header
header('Content-Type: application/json');

// Check authentication and permission
if (!AuthHandler::isAuthenticated() || !AuthHandler::hasPermission('board')) {
    echo json_encode([
        'success' => false,
        'message' => 'Nicht autorisiert'
    ]);
    exit;
}

// Get all open invitations
$db = Database::getUserDB();
$stmt = $db->prepare("
    SELECT 
        it.id,
        it.token,
        it.email,
        it.role,
        it.created_at,
        it.expires_at,
        u.email as created_by_email
    FROM invitation_tokens it
    LEFT JOIN users u ON it.created_by = u.id
    WHERE it.used_at IS NULL AND it.expires_at > NOW()
    ORDER BY it.created_at DESC
");
$stmt->execute();
$invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build invitation links
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

foreach ($invitations as &$invitation) {
    $invitation['link'] = $protocol . '://' . $host . '/pages/auth/register.php?token=' . $invitation['token'];
}

echo json_encode([
    'success' => true,
    'invitations' => $invitations
]);
