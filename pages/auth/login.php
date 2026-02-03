<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';

AuthHandler::startSession();

// Redirect if already authenticated
if (AuthHandler::isAuthenticated()) {
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
    
    // If 2FA code is provided, retrieve credentials from session
    if ($tfaCode !== null && isset($_SESSION['pending_2fa'])) {
        $email = $_SESSION['pending_2fa']['email'];
        $password = $_SESSION['pending_2fa']['password'];
    }
    
    $result = AuthHandler::login($email, $password, $tfaCode);
    
    if ($result['success']) {
        // Clear pending 2FA data
        unset($_SESSION['pending_2fa']);
        header('Location: ../dashboard/index.php');
        exit;
    } else {
        if (isset($result['require_2fa']) && $result['require_2fa']) {
            // Store credentials in session (server-side) for 2FA verification
            $_SESSION['pending_2fa'] = [
                'email' => $email,
                'password' => $password,
                'timestamp' => time()
            ];
            $require2FA = true;
        } else {
            $error = $result['message'];
            // Clear any pending 2FA data on error
            unset($_SESSION['pending_2fa']);
        }
    }
} else {
    // Check if we're in the middle of 2FA flow
    if (isset($_SESSION['pending_2fa'])) {
        // Verify the 2FA session hasn't expired (5 minutes)
        if (time() - $_SESSION['pending_2fa']['timestamp'] > 300) {
            unset($_SESSION['pending_2fa']);
            $error = '2FA-Sitzung abgelaufen. Bitte melden Sie sich erneut an.';
        } else {
            $require2FA = true;
        }
    }
}

$title = 'Login - IBC Intranet';
ob_start();
?>

<div class="flex items-center justify-center min-h-screen p-4">
    <div class="glass w-full max-w-md p-8 rounded-2xl shadow-2xl transition-all duration-300">
        <div class="text-center mb-8">
            <!-- IBC Logo -->
            <div class="mb-6">
                <img src="/intra/assets/img/ibc_logo_original.webp" alt="IBC Logo" class="mx-auto max-w-xs w-full h-auto">
            </div>
            
            <div class="inline-block p-4 bg-white/20 rounded-full mb-4 hover:bg-white/30 transition-all duration-300">
                <i class="fas fa-building text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">IBC Intranet</h1>
            <p class="text-white/80">Melden Sie sich an, um fortzufahren</p>
        </div>

        <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
        <div class="mb-6 p-4 bg-yellow-500/20 border border-yellow-500/50 rounded-lg text-white">
            <i class="fas fa-clock mr-2"></i>
            Aus Sicherheitsgründen wurdest du automatisch ausgeloggt.
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
        <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 rounded-lg text-white">
            <i class="fas fa-check-circle mr-2"></i>
            Erfolgreich abgemeldet.
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-white">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
            <?php if (!$require2FA): ?>
            <div>
                <label class="block text-white mb-2 font-medium">
                    <i class="fas fa-envelope mr-2"></i>E-Mail
                </label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white transition-all duration-300 hover:bg-white"
                    placeholder="ihre.email@beispiel.de"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <div>
                <label class="block text-white mb-2 font-medium">
                    <i class="fas fa-lock mr-2"></i>Passwort
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white transition-all duration-300 hover:bg-white"
                    placeholder="••••••••"
                >
            </div>
            <?php else: ?>
            <div>
                <label class="block text-white mb-2 font-medium">
                    <i class="fas fa-shield-alt mr-2"></i>2FA-Code
                </label>
                <input 
                    type="text" 
                    name="tfa_code" 
                    required 
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white transition-all duration-300 hover:bg-white text-center text-2xl tracking-widest"
                    placeholder="000000"
                    autofocus
                >
                <p class="mt-2 text-white/70 text-sm">Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein</p>
            </div>
            <?php endif; ?>

            <button 
                type="submit" 
                class="w-full py-4 px-6 bg-white text-purple-600 rounded-lg font-semibold hover:bg-white/90 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl shadow-lg active:scale-95"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                <?php echo $require2FA ? 'Code bestätigen' : 'Anmelden'; ?>
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-white/70 text-sm">
                Haben Sie Ihr Passwort vergessen? Wenden Sie sich an einen Administrator.
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/auth_layout.php';
