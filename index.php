<?php
// Fehleranzeige für Debugging aktivieren (nach Go-Live auf 0 setzen)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Konfiguration laden
require_once 'config/config.php';

// 2. Helper Funktionen laden (WICHTIG gegen den 500er Fehler)
require_once 'includes/helpers.php';

// 3. Authentifizierung laden
require_once 'src/Auth.php';

// 4. Weiterleitungs-Logik
if (Auth::check()) {
    // Wenn eingeloggt -> Dashboard
    header('Location: ' . BASE_URL . '/pages/dashboard/index.php');
    exit;
} else {
    // Wenn nicht eingeloggt -> Login
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
    <a href="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8'); ?>/pages/auth/login.php">
        <button>Weiter zum Login</button>
    </a>
</body>
</html>
