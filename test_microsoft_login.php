<?php
/**
 * Test endpoint for Microsoft Entra ID login
 * This file initiates the OAuth flow
 */

require_once __DIR__ . '/includes/handlers/AuthHandler.php';

try {
    AuthHandler::initiateMicrosoftLogin();
} catch (Exception $e) {
    echo "Error initiating Microsoft login: " . htmlspecialchars($e->getMessage());
    error_log("Microsoft login initiation error: " . $e->getMessage());
}
