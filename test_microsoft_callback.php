<?php
/**
 * Test endpoint for Microsoft Entra ID OAuth callback
 * This file handles the callback from Microsoft
 */

require_once __DIR__ . '/includes/handlers/AuthHandler.php';

try {
    AuthHandler::handleMicrosoftCallback();
} catch (Exception $e) {
    echo "Error handling Microsoft callback: " . htmlspecialchars($e->getMessage());
    error_log("Microsoft callback error: " . $e->getMessage());
}
