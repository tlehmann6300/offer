<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/User.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication and permission
if (!Auth::check() || !Auth::hasPermission('admin')) {
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

if (!in_array($newRole, ['member', 'alumni', 'manager', 'alumni_board', 'board', 'admin'])) {
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
        'message' => 'Sie können Ihre eigene Rolle nicht ändern'
    ]);
    exit;
}

// Update the role
if (User::update($userId, ['role' => $newRole])) {
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
