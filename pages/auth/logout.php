<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';

AuthHandler::logout();
header('Location: login.php?logout=1');
exit;
