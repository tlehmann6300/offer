<?php
// Set JSON response header
header('Content-Type: application/json');

// Disable error output in body
ini_set('display_errors', 0);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/User.php';

try {
    // Check authentication and permission
    if (!Auth::check() || !Auth::hasPermission('board')) {
        echo json_encode([
            'success' => false,
            'message' => 'Nicht autorisiert'
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

// Get POST data
$userId = intval($_POST['user_id'] ?? 0);
$newRole = $_POST['new_role'] ?? '';

// Validate input
if ($userId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Benutzer-ID'
    ]);
    exit;
}

if (!in_array($newRole, Auth::VALID_ROLES)) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Rolle'
    ]);
    exit;
}

// Check if user is trying to change their own role
if ($userId === $_SESSION['user_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'Du kannst Deine eigene Rolle nicht ändern'
    ]);
    exit;
}

    // Update the role and set prompt_profile_review flag
    // When role is updated by Board/Admin, prompt user to review their profile
    $updateData = [
        'role' => $newRole,
        'prompt_profile_review' => 1
    ];
    
    if (User::update($userId, $updateData)) {
        echo json_encode([
            'success' => true,
            'message' => 'Rolle erfolgreich geändert'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Ändern der Rolle'
        ]);
    }
} catch (Exception $e) {
    // Log the full error details
    error_log('Error in ajax_update_role.php: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    
    // Return generic JSON error response (don't expose internal details)
    echo json_encode([
        'success' => false,
        'message' => 'Server Fehler: Es ist ein interner Fehler aufgetreten. Bitte versuche es später erneut.'
    ]);
}
