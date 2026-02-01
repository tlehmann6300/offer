<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';

AuthHandler::startSession();

// Redirect if already authenticated
if (AuthHandler::isAuthenticated()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$error = '';
$require2FA = false;
$tempUserId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $tfaCode = $_POST['tfa_code'] ?? null;
    
    $result = AuthHandler::login($email, $password, $tfaCode);
    
    if ($result['success']) {
        header('Location: ../dashboard/index.php');
        exit;
    } else {
        if (isset($result['require_2fa']) && $result['require_2fa']) {
            $require2FA = true;
            $tempUserId = $result['user_id'];
        } else {
            $error = $result['message'];
        }
    }
}

$title = 'Login - IBC Intranet';
ob_start();
?>

<div class="flex items-center justify-center min-h-screen p-4">
    <div class="glass w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white/20 rounded-full mb-4">
                <i class="fas fa-building text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">IBC Intranet</h1>
            <p class="text-white/80">Melden Sie sich an, um fortzufahren</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-white">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <?php if (!$require2FA): ?>
            <div>
                <label class="block text-white mb-2 font-medium">
                    <i class="fas fa-envelope mr-2"></i>E-Mail
                </label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 transition"
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
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 transition"
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
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 transition text-center text-2xl tracking-widest"
                    placeholder="000000"
                    autofocus
                >
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>">
                <p class="mt-2 text-white/70 text-sm">Geben Sie den 6-stelligen Code aus Ihrer Authenticator-App ein</p>
            </div>
            <?php endif; ?>

            <button 
                type="submit" 
                class="w-full py-3 px-6 bg-white text-purple-600 rounded-lg font-semibold hover:bg-white/90 transition transform hover:scale-105 shadow-lg"
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
