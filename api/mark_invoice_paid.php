<?php
/**
 * API: Mark Invoice as Paid
 * Allows board members with 'Finanzen und Recht' position to mark invoices as paid
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../includes/models/Invoice.php';

header('Content-Type: application/json');

// Check authentication
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? '';

// Only board members can mark as paid
if ($userRole !== 'board') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Keine Berechtigung - nur Vorstandsmitglieder']);
    exit;
}

// Check if user has 'Finanzen und Recht' position in alumni_profiles
$contentDb = Database::getContentDB();
$stmt = $contentDb->prepare("
    SELECT position 
    FROM alumni_profiles 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

$hasFinancePosition = false;
if ($profile && !empty($profile['position'])) {
    // Check if position contains 'Finanzen und Recht' (case-insensitive)
    if (stripos($profile['position'], 'Finanzen und Recht') !== false) {
        $hasFinancePosition = true;
    }
}

if (!$hasFinancePosition) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'error' => 'Keine Berechtigung - Position "Finanzen und Recht" erforderlich'
    ]);
    exit;
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
    exit;
}

// Get invoice ID
$invoiceId = $_POST['invoice_id'] ?? null;

if (empty($invoiceId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invoice ID erforderlich']);
    exit;
}

// Mark invoice as paid
$result = Invoice::markAsPaid($invoiceId, $user['id']);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Rechnung erfolgreich als bezahlt markiert']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Fehler beim Markieren als bezahlt']);
}
