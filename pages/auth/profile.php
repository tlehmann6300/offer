<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/handlers/GoogleAuthenticator.php';
require_once __DIR__ . '/../../includes/models/User.php';

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user = Auth::user();
$message = '';
$error = '';
$showQRCode = false;
$qrCodeUrl = '';
$secret = '';

// Handle 2FA setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email'] ?? '');
        
        // Check if email has changed
        if ($newEmail !== $user['email']) {
            try {
                // Call User::updateEmail() to update the email
                if (User::updateEmail($user['id'], $newEmail)) {
                    // Update session with new email to prevent logout
                    $_SESSION['user_email'] = $newEmail;
                    $message = 'E-Mail-Adresse erfolgreich aktualisiert';
                    $user = Auth::user(); // Reload user data
                }
            } catch (Exception $e) {
                // Catch exceptions like 'E-Mail vergeben' or validation errors
                $error = $e->getMessage();
            }
        }
        // If email hasn't changed, just do nothing (user will see no message)
    } else if (isset($_POST['enable_2fa'])) {
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['email'], $secret, 'IBC Intranet');
        $showQRCode = true;
    } else if (isset($_POST['confirm_2fa'])) {
        $secret = $_POST['secret'] ?? '';
        $code = $_POST['code'] ?? '';
        
        $ga = new PHPGangsta_GoogleAuthenticator();
        if ($ga->verifyCode($secret, $code, 2)) {
            if (User::enable2FA($user['id'], $secret)) {
                $message = '2FA erfolgreich aktiviert';
                $user = Auth::user(); // Reload user
            } else {
                $error = 'Fehler beim Aktivieren von 2FA';
            }
        } else {
            $error = 'Ungültiger Code. Bitte versuchen Sie es erneut.';
            $secret = $_POST['secret'];
            $ga = new PHPGangsta_GoogleAuthenticator();
            $qrCodeUrl = $ga->getQRCodeGoogleUrl($user['email'], $secret, 'IBC Intranet');
            $showQRCode = true;
        }
    } else if (isset($_POST['disable_2fa'])) {
        if (User::disable2FA($user['id'])) {
            $message = '2FA erfolgreich deaktiviert';
            $user = Auth::user(); // Reload user
        } else {
            $error = 'Fehler beim Deaktivieren von 2FA';
        }
    } else if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $fullUser = User::getByEmail($user['email']);
        
        if (!password_verify($currentPassword, $fullUser['password_hash'])) {
            $error = 'Aktuelles Passwort ist falsch';
        } else if ($newPassword !== $confirmPassword) {
            $error = 'Neue Passwörter stimmen nicht überein';
        } else if (strlen($newPassword) < 8) {
            $error = 'Neues Passwort muss mindestens 8 Zeichen lang sein';
        } else {
            if (User::changePassword($user['id'], $newPassword)) {
                $message = 'Passwort erfolgreich geändert';
            } else {
                $error = 'Fehler beim Ändern des Passworts';
            }
        }
    } else if (isset($_POST['update_notifications'])) {
        $notifyNewProjects = isset($_POST['notify_new_projects']) ? true : false;
        $notifyNewEvents = isset($_POST['notify_new_events']) ? true : false;
        
        if (User::updateNotificationPreferences($user['id'], $notifyNewProjects, $notifyNewEvents)) {
            $message = 'Benachrichtigungseinstellungen erfolgreich aktualisiert';
            $user = Auth::user(); // Reload user data
        } else {
            $error = 'Fehler beim Aktualisieren der Benachrichtigungseinstellungen';
        }
    }
}

$title = 'Profil - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-user text-purple-600 mr-2"></i>
                Mein Profil
            </h1>
            <p class="text-gray-600">Verwalten Sie Ihre Kontoinformationen und Sicherheitseinstellungen</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="../inventory/my_rentals.php" class="btn-primary inline-block">
                <i class="fas fa-clipboard-list mr-2"></i>
                Meine Ausleihen
            </a>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Account Info -->
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            Kontoinformationen
        </h2>
        <div class="space-y-4">
            <div>
                <label class="text-sm text-gray-500">E-Mail</label>
                <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Rolle</label>
                <p class="text-lg">
                    <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full">
                        <?php 
                        $roleNames = [
                            'admin' => 'Administrator',
                            'board' => 'Vorstand',
                            'manager' => 'Ressortleiter',
                            'member' => 'Mitglied'
                        ];
                        echo $roleNames[$user['role']] ?? ucfirst($user['role']);
                        ?>
                    </span>
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Letzter Login</label>
                <p class="text-lg text-gray-800">
                    <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                </p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Mitglied seit</label>
                <p class="text-lg text-gray-800"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Update Email -->
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-envelope text-blue-600 mr-2"></i>
            E-Mail-Adresse ändern
        </h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">E-Mail-Adresse</label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    value="<?php echo htmlspecialchars($user['email']); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <button type="submit" name="update_email" class="w-full btn-primary">
                <i class="fas fa-save mr-2"></i>E-Mail-Adresse aktualisieren
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-key text-yellow-600 mr-2"></i>
            Passwort ändern
        </h2>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Aktuelles Passwort</label>
                <input 
                    type="password" 
                    name="current_password" 
                    required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Neues Passwort</label>
                <input 
                    type="password" 
                    name="new_password" 
                    required 
                    minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Passwort bestätigen</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    required 
                    minlength="8"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <button type="submit" name="change_password" class="w-full btn-primary">
                <i class="fas fa-save mr-2"></i>Passwort ändern
            </button>
        </form>
    </div>

    <!-- 2FA Settings -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                Zwei-Faktor-Authentifizierung (2FA)
            </h2>

            <?php if (!$showQRCode): ?>
            <div class="flex items-center justify-between mb-6">
                <div>
                    <p class="text-gray-700 mb-2">
                        Status: 
                        <?php if ($user['tfa_enabled']): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold">
                            <i class="fas fa-check-circle mr-1"></i>Aktiviert
                        </span>
                        <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-semibold">
                            <i class="fas fa-times-circle mr-1"></i>Deaktiviert
                        </span>
                        <?php endif; ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        Schützen Sie Ihr Konto mit einer zusätzlichen Sicherheitsebene
                    </p>
                </div>
                <div>
                    <?php if ($user['tfa_enabled']): ?>
                    <form method="POST" onsubmit="return confirm('Möchten Sie 2FA wirklich deaktivieren?');">
                        <button type="submit" name="disable_2fa" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-times mr-2"></i>2FA deaktivieren
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST">
                        <button type="submit" name="enable_2fa" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i>2FA aktivieren
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <p class="text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Empfehlung:</strong> Aktivieren Sie 2FA für zusätzliche Sicherheit. Sie benötigen eine Authenticator-App wie Google Authenticator oder Authy.
                </p>
            </div>
            <?php else: ?>
            <!-- QR Code Setup -->
            <div class="max-w-md mx-auto">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">2FA einrichten</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Scannen Sie den QR-Code mit Ihrer Authenticator-App und geben Sie den generierten Code ein
                    </p>
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="mx-auto mb-4 border-4 border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-500 mb-4">
                        Geheimer Schlüssel (manuell): <code class="bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($secret); ?></code>
                    </p>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="secret" value="<?php echo htmlspecialchars($secret); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">6-stelliger Code</label>
                        <input 
                            type="text" 
                            name="code" 
                            required 
                            maxlength="6"
                            pattern="[0-9]{6}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-center text-2xl tracking-widest"
                            placeholder="000000"
                            autofocus
                        >
                    </div>
                    <div class="flex space-x-4">
                        <a href="profile.php" class="flex-1 text-center px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            Abbrechen
                        </a>
                        <button type="submit" name="confirm_2fa" class="flex-1 btn-primary">
                            <i class="fas fa-check mr-2"></i>Bestätigen
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notification Settings -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-bell text-orange-600 mr-2"></i>
                Benachrichtigungen
            </h2>
            <p class="text-gray-600 mb-6">
                Wählen Sie aus, über welche Ereignisse Sie per E-Mail benachrichtigt werden möchten
            </p>
            
            <form method="POST" class="space-y-4">
                <div class="space-y-4">
                    <!-- New Projects Notification -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <input 
                            type="checkbox" 
                            name="notify_new_projects" 
                            id="notify_new_projects"
                            <?php echo ($user['notify_new_projects'] ?? true) ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                        >
                        <label for="notify_new_projects" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">Neue Projekte</span>
                            <span class="block text-sm text-gray-600">
                                Erhalten Sie eine E-Mail-Benachrichtigung, wenn ein neues Projekt veröffentlicht wird
                            </span>
                        </label>
                    </div>

                    <!-- New Events Notification -->
                    <div class="flex items-start p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <input 
                            type="checkbox" 
                            name="notify_new_events" 
                            id="notify_new_events"
                            <?php echo ($user['notify_new_events'] ?? false) ? 'checked' : ''; ?>
                            class="mt-1 h-5 w-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                        >
                        <label for="notify_new_events" class="ml-3 flex-1 cursor-pointer">
                            <span class="block text-sm font-medium text-gray-900">Neue Events</span>
                            <span class="block text-sm text-gray-600">
                                Erhalten Sie eine E-Mail-Benachrichtigung, wenn ein neues Event erstellt wird
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="update_notifications" class="w-full btn-primary">
                    <i class="fas fa-save mr-2"></i>Benachrichtigungseinstellungen speichern
                </button>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
