<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/User.php';

AuthHandler::startSession();

// Redirect if already authenticated
if (AuthHandler::isAuthenticated()) {
    header('Location: ../dashboard/index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$invitation = null;

if (empty($token)) {
    $error = 'Kein Einladungstoken angegeben';
} else {
    // Check if token is valid
    $db = Database::getUserDB();
    $stmt = $db->prepare("SELECT * FROM invitation_tokens WHERE token = ? AND used_at IS NULL AND expires_at > NOW()");
    $stmt->execute([$token]);
    $invitation = $stmt->fetch();
    
    if (!$invitation) {
        $error = 'Einladungstoken ist ungültig oder abgelaufen';
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $invitation) {
    $email = $invitation['email'];
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirmPassword) {
        $error = 'Passwörter stimmen nicht überein';
    } else if (strlen($password) < 8) {
        $error = 'Passwort muss mindestens 8 Zeichen lang sein';
    } else {
        // Check if user already exists
        $existing = User::getByEmail($email);
        if ($existing) {
            $error = 'Benutzer existiert bereits';
        } else {
            // Create user
            $userId = User::create($email, $password, $invitation['role']);
            
            if ($userId) {
                // Mark invitation as used
                $stmt = $db->prepare("UPDATE invitation_tokens SET used_at = NOW(), used_by = ? WHERE id = ?");
                $stmt->execute([$userId, $invitation['id']]);
                
                // Auto-login
                $result = AuthHandler::login($email, $password);
                if ($result['success']) {
                    header('Location: ../dashboard/index.php');
                    exit;
                }
            } else {
                $error = 'Fehler beim Erstellen des Benutzers';
            }
        }
    }
}

$title = 'Registrierung - IBC Intranet';
ob_start();
?>

<div class="flex items-center justify-center min-h-screen p-4">
    <div class="glass w-full max-w-md p-8 rounded-2xl shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white/20 rounded-full mb-4">
                <i class="fas fa-user-plus text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Registrierung</h1>
            <p class="text-white/80">Erstellen Sie Ihr Konto</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg text-white">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($invitation): ?>
        <form method="POST" class="space-y-6">
            <div class="bg-white/10 p-4 rounded-lg mb-4">
                <p class="text-white/80 text-sm mb-2">Sie wurden eingeladen als:</p>
                <p class="text-white font-semibold"><?php echo htmlspecialchars($invitation['email']); ?></p>
                <p class="text-white/60 text-xs mt-1">
                    Rolle: <?php 
                    $roleNames = [
                        'admin' => 'Administrator',
                        'board' => 'Vorstand',
                        'alumni_board' => 'Alumni-Vorstand',
                        'manager' => 'Ressortleiter',
                        'member' => 'Mitglied',
                        'alumni' => 'Alumni'
                    ];
                    echo $roleNames[$invitation['role']] ?? ucfirst($invitation['role']);
                    ?>
                </p>
                <?php if ($invitation['role'] === 'alumni'): ?>
                <div class="mt-3 p-2 bg-yellow-500/20 border border-yellow-500/30 rounded text-xs text-white/90">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Hinweis für Alumni:</strong> Ihr Profil wird nach der Registrierung vom Vorstand manuell geprüft und freigeschaltet, bevor Sie Zugriff auf interne Alumni-Netzwerkdaten erhalten.
                </div>
                <?php endif; ?>
            </div>

            <div>
                <label class="block text-white mb-2 font-medium">
                    <i class="fas fa-lock mr-2"></i>Passwort
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    minlength="8"
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 transition"
                    placeholder="Mindestens 8 Zeichen"
                >
            </div>

            <div>
                <label class="block text-white mb-2 font-medium">
                    <i class="fas fa-lock mr-2"></i>Passwort bestätigen
                </label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    required 
                    minlength="8"
                    class="w-full px-4 py-3 rounded-lg bg-white/90 border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50 transition"
                    placeholder="Passwort wiederholen"
                >
            </div>

            <button 
                type="submit" 
                class="w-full py-3 px-6 bg-white text-purple-600 rounded-lg font-semibold hover:bg-white/90 transition transform hover:scale-105 shadow-lg"
            >
                <i class="fas fa-user-check mr-2"></i>
                Konto erstellen
            </button>
        </form>
        <?php endif; ?>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-white/70 text-sm hover:text-white transition">
                <i class="fas fa-arrow-left mr-1"></i>
                Zurück zum Login
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/auth_layout.php';
