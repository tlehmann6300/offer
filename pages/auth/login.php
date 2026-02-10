<?php
// 1. Konfiguration laden
require_once __DIR__ . '/../../config/config.php';

// 2. Error reporting is configured in config.php based on ENVIRONMENT constant
// Detailed error display is only enabled in non-production environments
// This prevents information leakage (file paths, stack traces) in production

// 3. Weitere Abhängigkeiten laden
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../includes/helpers.php';

try {
    // 4. Logik in einem try-catch Block schützen
    // Redirect if already authenticated
    if (Auth::check()) {
        header('Location: ../dashboard/index.php');
        exit;
    }

    $error = '';
    $require2FA = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $tfaCode = $_POST['tfa_code'] ?? null;
        
        // Step 1: Initial login attempt (email and password)
        if ($tfaCode === null && !isset($_SESSION['pending_2fa'])) {
            // Verify credentials
            $user = Auth::verifyCredentials($email, $password);
            
            if (!$user) {
                $error = 'Ungültige Anmeldedaten';
            } else {
                // Check if account is permanently locked
                if (isset($user['is_locked_permanently']) && $user['is_locked_permanently']) {
                    $error = 'Account gesperrt. Bitte Admin kontaktieren.';
                } else if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                    $error = 'Zu viele Versuche. Wartezeit läuft.';
                } else if ($user['tfa_enabled']) {
                    // 2FA is enabled, store only user_id in session
                    $_SESSION['pending_2fa'] = [
                        'user_id' => $user['id'],
                        'timestamp' => time()
                    ];
                    $require2FA = true;
                } else {
                    // No 2FA, create session directly
                    Auth::createSession($user);
                    unset($_SESSION['pending_2fa']);
                    header('Location: ../dashboard/index.php');
                    exit;
                }
            }
        }
        // Step 2: 2FA verification
        else if ($tfaCode !== null && isset($_SESSION['pending_2fa'])) {
            $userId = $_SESSION['pending_2fa']['user_id'];
            
            // Fetch user by ID from database
            $db = Database::getUserDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Sitzung ungültig. Bitte melden Sie sich erneut an.';
                unset($_SESSION['pending_2fa']);
            } else {
                // Verify 2FA code
                require_once __DIR__ . '/../../includes/handlers/GoogleAuthenticator.php';
                $ga = new PHPGangsta_GoogleAuthenticator();
                
                if ($ga->verifyCode($user['tfa_secret'], $tfaCode, 2)) {
                    // 2FA code is valid, create session
                    Auth::createSession($user);
                    unset($_SESSION['pending_2fa']);
                    header('Location: ../dashboard/index.php');
                    exit;
                } else {
                    $error = 'Ungültiger 2FA-Code';
                    $require2FA = true;
                }
            }
        }
    } else {
        // GET request - check if there's a pending 2FA session
        if (isset($_SESSION['pending_2fa'])) {
            if (time() - $_SESSION['pending_2fa']['timestamp'] > 300) {
                unset($_SESSION['pending_2fa']);
                $error = '2FA-Sitzung abgelaufen. Bitte melden Sie sich erneut an.';
            } else {
                $require2FA = true;
            }
        }
    }

} catch (Throwable $e) {
    // 5. Display error information based on environment
    // Detailed error information is only shown in non-production environments to prevent information leakage
    echo '<div style="background-color: #fee2e2; border: 2px solid #ef4444; color: #991b1b; padding: 20px; font-family: sans-serif; margin: 20px; border-radius: 8px;">';
    echo '<h2 style="margin-top:0">Kritischer Fehler aufgetreten</h2>';
    
    if (ENVIRONMENT !== 'production') {
        // Show detailed error information only in non-production environments
        echo '<p><strong>Fehlermeldung:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Datei:</strong> ' . htmlspecialchars($e->getFile()) . ' (Zeile ' . $e->getLine() . ')</p>';
        echo '<pre style="background: #fff; padding: 10px; overflow: auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        // Log error details to server logs in production, but show generic message to user
        // This prevents information leakage while still allowing developers to debug issues
        error_log(sprintf(
            'Login error: %s in %s:%d. Stack trace: %s',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));
        
        // Show generic error message in production to prevent information leakage
        echo '<p>Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es später erneut oder wenden Sie sich an den Administrator.</p>';
    }
    
    echo '</div>';
    exit;
}

$title = 'Login - IBC Intranet';
ob_start();
?>

<div class="w-full max-w-md p-8 bg-white rounded-2xl shadow-2xl transition-all duration-300">
    <div class="text-center mb-8">
        <div class="inline-block p-4 bg-gradient-to-br from-blue-500 to-green-500 rounded-full mb-4 hover:shadow-lg transition-all duration-300">
            <i class="fas fa-building text-4xl text-white"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">IBC Intranet</h1>
        <p class="text-gray-600">Melden Sie sich an, um fortzufahren</p>
    </div>

    <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-yellow-800">
        <i class="fas fa-clock mr-2"></i>Aus Sicherheitsgründen wurdest du automatisch ausgeloggt.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-300 rounded-lg text-green-800">
        <i class="fas fa-check-circle mr-2"></i>Erfolgreich abgemeldet.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-lg text-red-800">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
        
        <?php if (!$require2FA): ?>
        <div>
            <label class="block text-gray-700 mb-2 font-medium"><i class="fas fa-envelope mr-2"></i>E-Mail</label>
            <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" placeholder="ihre.email@beispiel.de" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div>
            <label class="block text-gray-700 mb-2 font-medium"><i class="fas fa-lock mr-2"></i>Passwort</label>
            <input type="password" name="password" required class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-300" placeholder="••••••••">
        </div>
        <?php else: ?>
        <div>
            <label class="block text-gray-700 mb-2 font-medium"><i class="fas fa-shield-alt mr-2"></i>2FA-Code</label>
            <input type="text" name="tfa_code" required maxlength="6" pattern="[0-9]{6}" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 text-center text-2xl tracking-widest" placeholder="000000" autofocus>
            <p class="mt-2 text-gray-600 text-sm">Code aus Authenticator-App eingeben</p>
        </div>
        <?php endif; ?>

        <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-green-600 transition-all duration-300 transform hover:scale-105 shadow-lg">
            <i class="fas fa-sign-in-alt mr-2"></i><?php echo $require2FA ? 'Code bestätigen' : 'Anmelden'; ?>
        </button>
    </form>
    
    <div class="mt-6 text-center">
        <p class="text-gray-600 text-sm">Passwort vergessen? Wenden Sie sich an einen Administrator.</p>
    </div>
</div>

<?php
$content = ob_get_clean();
// Benutze require statt include für Layout, damit Fehler sichtbar werden
require __DIR__ . '/../../includes/templates/auth_layout.php';
?>