<?php
require_once __DIR__ . '/includes/handlers/AuthHandler.php';

AuthHandler::startSession();

// Redirect if already authenticated
if (AuthHandler::isAuthenticated()) {
    header('Location: pages/dashboard/index.php');
    exit;
}

// Redirect to login
header('Location: pages/auth/login.php');
exit;
