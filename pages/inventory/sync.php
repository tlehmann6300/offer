<?php
/**
 * EasyVerein Synchronization Page
 * Handles synchronization of inventory items from EasyVerein
 */

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/services/EasyVereinSync.php';

// Check authentication and permissions
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

// Only managers can perform synchronization
if (!Auth::hasPermission('manager')) {
    $_SESSION['error'] = 'Sie haben keine Berechtigung, diese Aktion auszuführen.';
    header('Location: index.php');
    exit;
}

// Perform synchronization
$sync = new EasyVereinSync();
$result = $sync->sync($_SESSION['user_id']);

// Store results in session for display
$_SESSION['sync_result'] = $result;

// Redirect back to inventory index
header('Location: index.php');
exit;
