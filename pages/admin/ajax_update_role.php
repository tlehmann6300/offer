<?php
// Set JSON response header
header('Content-Type: application/json');

// Disable error output in body
ini_set('display_errors', 0);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/User.php';

try {
    // Check authentication and permission
    if (!Auth::check() || !Auth::canManageUsers()) {
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
$successorId = isset($_POST['successor_id']) ? intval($_POST['successor_id']) : null;

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
$isOwnRole = ($userId === $_SESSION['user_id']);

// Get current user's role for checks
$currentUserRole = $_SESSION['user_role'] ?? '';
$isBoardMember = in_array($currentUserRole, Auth::BOARD_ROLES);

// If board member is demoting themselves to member or alumni, require a successor
if ($isOwnRole && $isBoardMember && in_array($newRole, ['member', 'alumni'])) {
    if (!$successorId || $successorId <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Ein Nachfolger muss bestimmt werden'
        ]);
        exit;
    }
    
    // Validate successor exists and has appropriate role
    $successor = User::getById($successorId);
    if (!$successor) {
        echo json_encode([
            'success' => false,
            'message' => 'Ungültiger Nachfolger'
        ]);
        exit;
    }
    
    // Update successor's role to the current user's board role
    if (!User::update($successorId, [
        'role' => $currentUserRole,
        'prompt_profile_review' => 1
    ])) {
        echo json_encode([
            'success' => false,
            'message' => 'Fehler beim Zuweisen der Rolle an den Nachfolger'
        ]);
        exit;
    }
}

// For non-succession cases where board member is trying to demote themselves, prevent it
// Regular users can still change their own roles through other means if allowed
if ($isOwnRole && $isBoardMember && in_array($newRole, ['member', 'alumni']) && !$successorId) {
    echo json_encode([
        'success' => false,
        'message' => 'Du kannst deine Vorstandsrolle nicht ohne Nachfolger abgeben'
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
        $message = 'Rolle erfolgreich geändert';
        
        // Add succession message if applicable
        if ($isOwnRole && $successorId) {
            $successorUser = User::getById($successorId);
            $message = 'Rollenwechsel erfolgreich durchgeführt. Deine Rolle wurde an ' . 
                       $successorUser['email'] . ' übertragen.';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
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
