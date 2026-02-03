<?php
require_once 'config/config.php';
require_once 'src/Auth.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/pages/dashboard/index.php');
    exit;
} else {
    header('Location: ' . BASE_URL . '/pages/auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weiterleitung</title>
</head>
<body>
    <p>Falls die automatische Weiterleitung nicht funktioniert:</p>
    <a href="<?php echo BASE_URL; ?>/pages/auth/login.php">
        <button>Weiter zum Login</button>
    </a>
</body>
</html>
