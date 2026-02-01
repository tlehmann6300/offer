<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';

AuthHandler::logout();
header('Location: login.php');
exit;
