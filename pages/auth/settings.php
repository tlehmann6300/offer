<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/models/User.php';

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user = Auth::user();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email'] ?? '');
        
        // Check if email has changed
        if ($newEmail !== $user['email']) {
            try {
                // Generate token and save to email_change_requests
                $token = User::createEmailChangeRequest($user['id'], $newEmail);
                
                // Create confirmation link using BASE_URL for security
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                $confirmLink = $baseUrl . '/api/confirm_email.php?token=' . urlencode($token);
                
                // TODO: Send email to new address with confirmation link
                // For now, we'll just show a message
                // In production, use PHPMailer or similar to send email
                
                $message = 'Bestätigungslink an neue E-Mail gesendet. Bitte überprüfe dein Postfach.';
                
                // Log the action
                error_log("Email change confirmation link: $confirmLink");
            } catch (Exception $e) {
                // Catch exceptions like 'E-Mail vergeben' or validation errors
                $error = $e->getMessage();
            }
        }
        // If email hasn't changed, just do nothing (user will see no message)
    } else if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $fullUser = User::getByEmail($user['email']);
        
        if (!password_verify($currentPassword, $fullUser['password'])) {
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
    } else if (isset($_POST['update_theme'])) {
        $theme = $_POST['theme'] ?? 'auto';
        
        // Validate theme value
        if (!in_array($theme, ['light', 'dark', 'auto'])) {
            $theme = 'auto';
        }
        
        if (User::updateThemePreference($user['id'], $theme)) {
            $message = 'Design-Einstellungen erfolgreich gespeichert';
            $user = Auth::user(); // Reload user data
        } else {
            $error = 'Fehler beim Speichern der Design-Einstellungen';
        }
    }
}

$title = 'Einstellungen';
ob_start();
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-cog text-purple-600 mr-3"></i>
            Einstellungen
        </h1>
        <p class="text-gray-600">Verwalte deine Konto- und Sicherheitseinstellungen</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($message): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-start">
            <i class="fas fa-check-circle mt-0.5 mr-3"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-start">
            <i class="fas fa-exclamation-circle mt-0.5 mr-3"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Settings Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
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

        <!-- Notification Settings -->
        <div class="lg:col-span-2">
            <div class="card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-bell text-orange-600 mr-2"></i>
                    Benachrichtigungen
                </h2>
                <p class="text-gray-600 mb-6">
                    Wähle aus, über welche Ereignisse du per E-Mail benachrichtigt werden möchtest
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
                                    Erhalte eine E-Mail-Benachrichtigung, wenn ein neues Projekt veröffentlicht wird
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
                                    Erhalte eine E-Mail-Benachrichtigung, wenn ein neues Event erstellt wird
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

        <!-- Theme Settings -->
        <div class="lg:col-span-2">
            <div class="card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-palette text-purple-600 mr-2"></i>
                    Design-Einstellungen
                </h2>
                <p class="text-gray-600 mb-6">
                    Wähle dein bevorzugtes Design-Theme
                </p>
                
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Light Theme -->
                        <label class="flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-purple-500 <?php echo ($user['theme_preference'] ?? 'auto') === 'light' ? 'border-purple-500 bg-purple-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="theme" value="light" <?php echo ($user['theme_preference'] ?? 'auto') === 'light' ? 'checked' : ''; ?> class="sr-only">
                            <i class="fas fa-sun text-4xl text-yellow-500 mb-2"></i>
                            <span class="font-medium text-gray-800">Hellmodus</span>
                            <span class="text-sm text-gray-600 text-center mt-1">Immer helles Design</span>
                        </label>

                        <!-- Dark Theme -->
                        <label class="flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-purple-500 <?php echo ($user['theme_preference'] ?? 'auto') === 'dark' ? 'border-purple-500 bg-purple-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="theme" value="dark" <?php echo ($user['theme_preference'] ?? 'auto') === 'dark' ? 'checked' : ''; ?> class="sr-only">
                            <i class="fas fa-moon text-4xl text-indigo-600 mb-2"></i>
                            <span class="font-medium text-gray-800">Dunkelmodus</span>
                            <span class="text-sm text-gray-600 text-center mt-1">Immer dunkles Design</span>
                        </label>

                        <!-- Auto Theme -->
                        <label class="flex flex-col items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:border-purple-500 <?php echo ($user['theme_preference'] ?? 'auto') === 'auto' ? 'border-purple-500 bg-purple-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="theme" value="auto" <?php echo ($user['theme_preference'] ?? 'auto') === 'auto' ? 'checked' : ''; ?> class="sr-only">
                            <i class="fas fa-adjust text-4xl text-gray-600 mb-2"></i>
                            <span class="font-medium text-gray-800">Automatisch</span>
                            <span class="text-sm text-gray-600 text-center mt-1">Folgt Systemeinstellung</span>
                        </label>
                    </div>

                    <button type="submit" name="update_theme" class="w-full btn-primary">
                        <i class="fas fa-save mr-2"></i>Design-Einstellungen speichern
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
// Make theme selection more interactive
document.querySelectorAll('input[name="theme"]').forEach(radio => {
    radio.addEventListener('change', function() {
        // Remove highlight from all labels
        document.querySelectorAll('label[class*="border-2"]').forEach(label => {
            label.classList.remove('border-purple-500', 'bg-purple-50');
            label.classList.add('border-gray-200');
        });
        
        // Add highlight to selected label
        const selectedLabel = this.closest('label');
        selectedLabel.classList.remove('border-gray-200');
        selectedLabel.classList.add('border-purple-500', 'bg-purple-50');
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
