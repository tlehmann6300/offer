<?php
/**
 * API: Submit Invoice
 * Handles invoice submission with file upload
 */

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../includes/models/Invoice.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check authentication
if (!Auth::check()) {
    $_SESSION['error_message'] = 'Nicht authentifiziert';
    header('Location: ' . asset('pages/auth/login.php'));
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? '';

// Check if user has permission to submit invoices
$allowedRoles = ['board', 'vorstand_intern', 'vorstand_extern', 'vorstand_finanzen_recht', 'alumni_board', 'alumni_finanzprufer', 'head'];
if (!in_array($userRole, $allowedRoles)) {
    $_SESSION['error_message'] = 'Keine Berechtigung';
    header('Location: ' . asset('pages/dashboard/index.php'));
    exit;
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Methode nicht erlaubt';
    header('Location: ' . asset('pages/invoices/index.php'));
    exit;
}

// Validate required fields
$amount = $_POST['amount'] ?? null;
$description = $_POST['description'] ?? null;
$date = $_POST['date'] ?? null;

if (empty($amount) || empty($description) || empty($date)) {
    $_SESSION['error_message'] = 'Alle Felder sind erforderlich';
    header('Location: ' . asset('pages/invoices/index.php'));
    exit;
}

// Validate amount
if (!is_numeric($amount) || $amount <= 0) {
    $_SESSION['error_message'] = 'Ungültiger Betrag';
    header('Location: ' . asset('pages/invoices/index.php'));
    exit;
}

// Validate file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'Datei-Upload fehlgeschlagen';
    header('Location: ' . asset('pages/invoices/index.php'));
    exit;
}

// Create invoice using Invoice model
$result = Invoice::create($user['id'], [
    'description' => $description,
    'amount' => $amount
], $_FILES['file']);

if ($result['success']) {
    $_SESSION['success_message'] = 'Rechnung erfolgreich eingereicht';
} else {
    $_SESSION['error_message'] = $result['error'] ?? 'Fehler beim Einreichen der Rechnung';
}

header('Location: ' . asset('pages/invoices/index.php'));
exit;
