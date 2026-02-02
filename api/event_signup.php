<?php
/**
 * Event Signup API
 * Handles event and helper slot signups/cancellations
 */

require_once __DIR__ . '/../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../includes/models/Event.php';
require_once __DIR__ . '/../includes/database.php';

// Start session and check authentication
AuthHandler::startSession();

header('Content-Type: application/json');

// Check authentication
if (!AuthHandler::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht authentifiziert']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;

$user = AuthHandler::getCurrentUser();
$userId = $user['id'];
$userRole = $_SESSION['user_role'] ?? 'member';

try {
    switch ($action) {
        case 'signup':
            // Get parameters
            $eventId = $input['event_id'] ?? null;
            $slotId = $input['slot_id'] ?? null;
            $slotStart = $input['slot_start'] ?? null;
            $slotEnd = $input['slot_end'] ?? null;
            
            if (!$eventId) {
                throw new Exception('Event-ID fehlt');
            }
            
            // Get event details
            $event = Event::getById($eventId, false);
            if (!$event) {
                throw new Exception('Event nicht gefunden');
            }
            
            // Check if user already has a signup for this event
            $userSignups = Event::getUserSignups($userId);
            $existingSignup = null;
            foreach ($userSignups as $signup) {
                if ($signup['event_id'] == $eventId && $signup['status'] !== 'cancelled') {
                    $existingSignup = $signup;
                    break;
                }
            }
            
            // If signing up for a slot, check for time conflicts
            if ($slotId && $slotStart && $slotEnd) {
                // Prevent double booking - check if user has another slot at same time
                $db = Database::getContentDB();
                
                $stmt = $db->prepare("
                    SELECT s.id, s.event_id, e.title, es.start_time, es.end_time
                    FROM event_signups s
                    JOIN event_slots es ON s.slot_id = es.id
                    JOIN events e ON s.event_id = e.id
                    WHERE s.user_id = ? 
                    AND s.status = 'confirmed'
                    AND s.slot_id IS NOT NULL
                    AND s.event_id != ?
                    AND es.start_time < ?
                    AND es.end_time > ?
                ");
                
                $stmt->execute([
                    $userId,
                    $eventId,
                    $slotEnd,      // Existing slot starts before new slot ends
                    $slotStart     // Existing slot ends after new slot starts
                ]);
                
                $conflicts = $stmt->fetchAll();
                
                if (!empty($conflicts)) {
                    $conflictEvent = $conflicts[0];
                    throw new Exception('Sie haben bereits einen Helfer-Slot zur gleichen Zeit: ' . 
                                      htmlspecialchars($conflictEvent['title']));
                }
                
                // If user already has a general signup, we'll upgrade it to slot signup
                // Otherwise create new signup
            }
            
            // Perform signup
            if ($existingSignup && !$slotId) {
                // User is already signed up for general event
                throw new Exception('Sie sind bereits für dieses Event angemeldet');
            } elseif ($existingSignup && $slotId) {
                // User has general signup but now wants to sign up for a slot
                // We need to update the existing signup or create a new one for the slot
                // For simplicity, we'll cancel the old one and create a new one
                Event::cancelSignup($existingSignup['id'], $userId);
            }
            
            $result = Event::signup($eventId, $userId, $slotId, $userRole);
            
            echo json_encode([
                'success' => true,
                'message' => 'Anmeldung erfolgreich',
                'signup_id' => $result['id'],
                'status' => $result['status']
            ]);
            break;
            
        case 'cancel':
            // Get parameters
            $signupId = $input['signup_id'] ?? null;
            
            if (!$signupId) {
                throw new Exception('Signup-ID fehlt');
            }
            
            // Get signup to check it belongs to user
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                SELECT s.*, e.start_time 
                FROM event_signups s
                JOIN events e ON s.event_id = e.id
                WHERE s.id = ? AND s.user_id = ?
            ");
            $stmt->execute([$signupId, $userId]);
            $signup = $stmt->fetch();
            
            if (!$signup) {
                throw new Exception('Anmeldung nicht gefunden');
            }
            
            // Check if cancellation is still allowed (before event start)
            if (strtotime($signup['start_time']) <= time()) {
                throw new Exception('Abmeldung nicht mehr möglich (Event hat bereits begonnen)');
            }
            
            // Cancel signup
            Event::cancelSignup($signupId, $userId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Abmeldung erfolgreich'
            ]);
            break;
            
        default:
            throw new Exception('Ungültige Aktion');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
