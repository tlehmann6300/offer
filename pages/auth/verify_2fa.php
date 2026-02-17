<?php
/**
 * Two-Factor Authentication Verification Page
 * Users are redirected here after password login when 2FA is enabled.
 * The session is not fully authenticated until the 2FA code is verified.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/models/User.php';
require_once __DIR__ . '/../../includes/handlers/GoogleAuthenticator.php';
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';

// Start session securely
AuthHandler::startSession();

// Redirect if no pending 2FA verification
if (!isset($_SESSION['2fa_pending']) || $_SESSION['2fa_pending'] !== true || !isset($_SESSION['2fa_pending_user_id'])) {
    $loginUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/auth/login.php' : '/pages/auth/login.php';
    header('Location: ' . $loginUrl);
    exit;
}

// Redirect if already fully authenticated
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    $dashboardUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/dashboard/index.php' : '/pages/dashboard/index.php';
    header('Location: ' . $dashboardUrl);
    exit;
}

$error = '';
$pendingUserId = intval($_SESSION['2fa_pending_user_id']);

// Handle 2FA code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';

    // Load user from database
    $db = Database::getUserDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$pendingUserId]);
    $user = $stmt->fetch();

    if ($user && $user['tfa_enabled'] && $user['tfa_secret']) {
        $ga = new PHPGangsta_GoogleAuthenticator();

        if ($ga->verifyCode($user['tfa_secret'], $code, 2)) {
            // 2FA verified successfully - complete login
            // Reset failed attempts and update last login
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);

            // Clear 2FA pending state
            unset($_SESSION['2fa_pending']);
            unset($_SESSION['2fa_pending_user_id']);

            // Set full authentication session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['profile_incomplete'] = !$user['profile_complete'];

            // Redirect to dashboard
            $dashboardUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/dashboard/index.php' : '/pages/dashboard/index.php';
            header('Location: ' . $dashboardUrl);
            exit;
        } else {
            $error = 'Ungültiger 2FA-Code. Bitte versuche es erneut.';
        }
    } else {
        // User not found or 2FA not configured - clear state and redirect
        unset($_SESSION['2fa_pending']);
        unset($_SESSION['2fa_pending_user_id']);
        $loginUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/auth/login.php' : '/pages/auth/login.php';
        header('Location: ' . $loginUrl);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA-Verifizierung - IBC Intranet</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Arial, sans-serif;
            background: #0a0f1e;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .card h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
            text-align: center;
        }

        .card p {
            color: #666;
            text-align: center;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .shield-icon {
            text-align: center;
            margin-bottom: 20px;
        }

        .shield-icon svg {
            width: 64px;
            height: 64px;
            fill: #6366f1;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
            color: #1a1a2e;
            transition: border-color 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.1s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="shield-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
            </div>
            <h1>Zwei-Faktor-Authentifizierung</h1>
            <p>Gib den 6-stelligen Code aus deiner Authenticator-App ein</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="code">Authentifizierungscode</label>
                    <input 
                        type="text" 
                        id="code"
                        name="code" 
                        required 
                        maxlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        autocomplete="one-time-code"
                        autofocus
                    >
                </div>
                <button type="submit" class="btn-primary">Verifizieren</button>
            </form>

            <a href="login.php" class="back-link">Zurück zur Anmeldung</a>
        </div>
    </div>
</body>
</html>
