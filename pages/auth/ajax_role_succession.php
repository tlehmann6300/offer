<?php
/**
 * AJAX endpoint for handling role succession when a board member demotes themselves
 */

// Set JSON response header
header('Content-Type: application/json');

// Disable error output in body
ini_set('display_errors', 0);

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/User.php';

try {
    // Check authentication
    if (!Auth::check()) {
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

    $currentUser = Auth::user();
    $currentUserId = $currentUser['id'];
    $currentRole = $currentUser['role'];
    
    // Get POST data
    $newRole = $_POST['new_role'] ?? '';
    $successorId = isset($_POST['successor_id']) ? intval($_POST['successor_id']) : 0;
    
    // Validate that current user has a board role
    if (!in_array($currentRole, Auth::BOARD_ROLES)) {
        echo json_encode([
            'success' => false,
            'message' => 'Du bist kein Vorstandsmitglied'
        ]);
        exit;
    }
    
    // Validate new role
    if (!in_array($newRole, Auth::VALID_ROLES)) {
        echo json_encode([
            'success' => false,
            'message' => 'Ungültige neue Rolle'
        ]);
        exit;
    }
    
    // Check if this is a demotion from board to non-board
    $isNewRoleBoard = in_array($newRole, Auth::BOARD_ROLES);
    $isDemotion = !$isNewRoleBoard;
    
    if ($isDemotion) {
        // Demotion requires a successor
        if ($successorId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Nachfolger muss ausgewählt werden',
                'requiresSuccessor' => true
            ]);
            exit;
        }
        
        // Validate successor
        $successor = User::getById($successorId);
        if (!$successor) {
            echo json_encode([
                'success' => false,
                'message' => 'Nachfolger nicht gefunden'
            ]);
            exit;
        }
        
        // Successor must not be the same as current user
        if ($successorId === $currentUserId) {
            echo json_encode([
                'success' => false,
                'message' => 'Du kannst nicht dein eigener Nachfolger sein'
            ]);
            exit;
        }
        
        // Successor must have member role to be eligible
        if ($successor['role'] !== 'member') {
            echo json_encode([
                'success' => false,
                'message' => 'Nachfolger muss ein reguläres Mitglied sein'
            ]);
            exit;
        }
        
        // Perform atomic role swap using transaction
        $db = Database::getUserDB();
        
        try {
            $db->beginTransaction();
            
            // Update current user to new role (demotion)
            $stmt = $db->prepare("UPDATE users SET role = ?, prompt_profile_review = 1 WHERE id = ?");
            $stmt->execute([$newRole, $currentUserId]);
            
            // Update successor to inherit the exact board role
            $stmt = $db->prepare("UPDATE users SET role = ?, prompt_profile_review = 1 WHERE id = ?");
            $stmt->execute([$currentRole, $successorId]);
            
            $db->commit();
            
            // Update session with new role
            $_SESSION['user_role'] = $newRole;
            
            echo json_encode([
                'success' => true,
                'message' => 'Rollenwechsel erfolgreich durchgeführt',
                'redirect' => '/pages/dashboard/index.php'
            ]);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log('Role succession transaction failed: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Rollenwechsel'
            ]);
        }
        
    } else {
        // Not a demotion, just update the role normally
        if (User::update($currentUserId, ['role' => $newRole, 'prompt_profile_review' => 1])) {
            $_SESSION['user_role'] = $newRole;
            
            echo json_encode([
                'success' => true,
                'message' => 'Rolle erfolgreich geändert',
                'redirect' => '/pages/dashboard/index.php'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Ändern der Rolle'
            ]);
        }
    }
    
} catch (Exception $e) {
    // Log the full error details
    error_log('Error in ajax_role_succession.php: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    
    // Return generic JSON error response (don't expose internal details)
    echo json_encode([
        'success' => false,
        'message' => 'Server Fehler: Es ist ein interner Fehler aufgetreten. Bitte versuche es später erneut.'
    ]);
}
