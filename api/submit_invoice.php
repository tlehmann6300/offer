<?php
/**
 * API: Submit Invoice
 * Handles invoice submission with file upload
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../includes/models/Invoice.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? '';

// Check if user has permission to submit invoices
$allowedRoles = ['admin', 'board', 'alumni_board', 'head'];
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
    exit;
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

// Validate required fields
$amount = $_POST['amount'] ?? null;
$description = $_POST['description'] ?? null;
$date = $_POST['date'] ?? null;

if (empty($amount) || empty($description) || empty($date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Alle Felder sind erforderlich']);
    exit;
}

// Validate amount
if (!is_numeric($amount) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültiger Betrag']);
    exit;
}

// Validate file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datei-Upload fehlgeschlagen']);
    exit;
}

// Create invoice using Invoice model
$result = Invoice::create($user['id'], [
    'description' => $description,
    'amount' => $amount
], $_FILES['file']);

if ($result['success']) {
    // Set success message in session
    session_start();
    $_SESSION['success_message'] = 'Rechnung erfolgreich eingereicht';
    
    // Redirect to invoices page
    header('Location: ' . asset('pages/invoices/index.php'));
    exit;
} else {
    // Set error message in session
    session_start();
    $_SESSION['error_message'] = $result['error'] ?? 'Fehler beim Einreichen der Rechnung';
    
    // Redirect to invoices page
    header('Location: ' . asset('pages/invoices/index.php'));
    exit;
}
