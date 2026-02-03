<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../includes/helpers.php';

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
    
    // If 2FA code is provided, retrieve credentials from session
    if ($tfaCode !== null && isset($_SESSION['pending_2fa'])) {
        $email = $_SESSION['pending_2fa']['email'];
        $password = $_SESSION['pending_2fa']['password'];
    }
    
    $result = Auth::login($email, $password, $tfaCode);
    
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
        <i class="fas fa-clock mr-2"></i>
        Aus Sicherheitsgründen wurdest du automatisch ausgeloggt.
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout']) && $_GET['logout'] == 1): ?>
    <div class="mb-6 p-4 bg-green-50 border border-green-300 rounded-lg text-green-800">
        <i class="fas fa-check-circle mr-2"></i>
        Erfolgreich abgemeldet.
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-lg text-red-800">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
            <?php if (!$require2FA): ?>
            <div>
                <label class="block text-gray-700 mb-2 font-medium">
                    <i class="fas fa-envelope mr-2"></i>E-Mail
                </label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                    placeholder="ihre.email@beispiel.de"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <div>
                <label class="block text-gray-700 mb-2 font-medium">
                    <i class="fas fa-lock mr-2"></i>Passwort
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300"
                    placeholder="••••••••"
                >
            </div>
            <?php else: ?>
            <div>
                <label class="block text-gray-700 mb-2 font-medium">
                    <i class="fas fa-shield-alt mr-2"></i>2FA-Code
                </label>
                <input 
                    type="text" 
                    name="tfa_code" 
                    required 
                    maxlength="6"
                    pattern="[0-9]{6}"
                    class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 text-center text-2xl tracking-widest"
                    placeholder="000000"
                    autofocus
                >
                <p class="mt-2 text-gray-600 text-sm">Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein</p>
            </div>
            <?php endif; ?>

            <button 
                type="submit" 
                class="w-full py-4 px-6 bg-gradient-to-r from-blue-500 to-green-500 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-green-600 transition-all duration-300 transform hover:scale-105 hover:shadow-xl shadow-lg active:scale-95"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>
                <?php echo $require2FA ? 'Code bestätigen' : 'Anmelden'; ?>
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600 text-sm">
                Haben Sie Ihr Passwort vergessen? Wenden Sie sich an einen Administrator.
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/auth_layout.php';
