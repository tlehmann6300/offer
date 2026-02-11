<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/models/User.php';
require_once __DIR__ . '/../../src/MailService.php';

if (!Auth::check()) {
    header('Location: login.php');
    exit;
}

$user = Auth::user();
$message = '';
$error = '';

// Check for session messages (from email confirmation, etc.)
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email'] ?? '');
        
        // Check if email has changed
        if ($newEmail !== $user['email']) {
            try {
                // Generate token and save to email_change_requests
                $token = User::createEmailChangeRequest($user['id'], $newEmail);
                
                // Send confirmation email to new address
                $emailSent = MailService::sendEmailChangeConfirmation($newEmail, $token);
                
                if ($emailSent) {
                    $message = 'Bestätigungslink an neue E-Mail gesendet. Bitte überprüfe dein Postfach.';
                } else {
                    // Email sending failed, but token is created
                    // Log the link for testing/debugging purposes
                    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                    $confirmLink = $baseUrl . '/api/confirm_email.php?token=' . urlencode($token);
                    error_log("Email sending failed. Confirmation link: $confirmLink");
                    
                    $message = 'Die E-Mail-Änderung konnte nicht abgeschlossen werden. Bitte versuche es später erneut oder kontaktiere den Administrator.';
                }
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">E-Mail-Adresse</label>
                    <input 
                        type="email" 
                        name="email" 
                        required 
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                        class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Aktuelles Passwort</label>
                    <input 
                        type="password" 
                        name="current_password" 
                        required 
                        class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Neues Passwort</label>
                    <input 
                        type="password" 
                        name="new_password" 
                        required 
                        minlength="8"
                        class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Passwort bestätigen</label>
                    <input 
                        type="password" 
                        name="confirm_password" 
                        required 
                        minlength="8"
                        class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
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
                        <div class="flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <input 
                                type="checkbox" 
                                name="notify_new_projects" 
                                id="notify_new_projects"
                                <?php echo ($user['notify_new_projects'] ?? true) ? 'checked' : ''; ?>
                                class="mt-1 h-5 w-5 text-purple-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:focus:ring-blue-500"
                            >
                            <label for="notify_new_projects" class="ml-3 flex-1 cursor-pointer">
                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">Neue Projekte</span>
                                <span class="block text-sm text-gray-600 dark:text-gray-400">
                                    Erhalte eine E-Mail-Benachrichtigung, wenn ein neues Projekt veröffentlicht wird
                                </span>
                            </label>
                        </div>

                        <!-- New Events Notification -->
                        <div class="flex items-start p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <input 
                                type="checkbox" 
                                name="notify_new_events" 
                                id="notify_new_events"
                                <?php echo ($user['notify_new_events'] ?? false) ? 'checked' : ''; ?>
                                class="mt-1 h-5 w-5 text-purple-600 bg-white border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:focus:ring-blue-500"
                            >
                            <label for="notify_new_events" class="ml-3 flex-1 cursor-pointer">
                                <span class="block text-sm font-medium text-gray-900 dark:text-gray-100">Neue Events</span>
                                <span class="block text-sm text-gray-600 dark:text-gray-400">
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

        <!-- Role Change (Board Members Only) -->
        <?php if (Auth::isBoardMember()): ?>
        <div class="lg:col-span-2">
            <div class="card p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-user-shield text-red-600 mr-2"></i>
                    Rolle ändern
                </h2>
                <p class="text-gray-600 mb-6">
                    <strong>Hinweis:</strong> Wenn du deine Vorstandsrolle zu "Mitglied" änderst, musst du einen Nachfolger bestimmen, der deine Rolle übernimmt.
                </p>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Aktuelle Rolle</label>
                        <input 
                            type="text" 
                            readonly
                            value="<?php echo htmlspecialchars(translateRole($user['role'])); ?>"
                            class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-300 rounded-lg cursor-not-allowed"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Neue Rolle</label>
                        <select 
                            id="newRoleSelect"
                            class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        >
                            <option value="">-- Bitte wählen --</option>
                            <option value="member">Mitglied</option>
                            <option value="head">Ressortleiter</option>
                            <option value="board_finance">Vorstand Finanzen & Recht</option>
                            <option value="board_internal">Vorstand Intern</option>
                            <option value="board_external">Vorstand Extern</option>
                        </select>
                    </div>
                    
                    <button type="button" id="changeRoleBtn" class="w-full btn-primary">
                        <i class="fas fa-exchange-alt mr-2"></i>Rolle ändern
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- Role Succession Modal -->
<div id="successionModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                    Nachfolger bestimmen
                </h3>
                <button id="closeSuccessionModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800">
                <p class="font-medium mb-2">Du bist dabei, deine Vorstandsrolle aufzugeben.</p>
                <p>Bitte wähle ein Mitglied aus, das deine exakte Vorstandsrolle übernehmen soll: <strong id="currentRoleDisplay"><?php echo htmlspecialchars(translateRole($user['role'])); ?></strong></p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nachfolger auswählen</label>
                <select 
                    id="successorSelect"
                    class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                    <option value="">-- Bitte wählen --</option>
                    <?php 
                    // Get users with 'member' role for successor selection
                    // Only 'member' role users are eligible to become board members
                    $members = User::getAll('member');
                    foreach ($members as $member):
                        if ($member['id'] != $user['id']): // Exclude current user
                    ?>
                    <option value="<?php echo $member['id']; ?>">
                        <?php echo htmlspecialchars($member['email']); ?>
                    </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button id="cancelSuccession" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Abbrechen
                </button>
                <button id="confirmSuccession" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 dark:hover:bg-purple-600">
                    <i class="fas fa-check mr-2"></i>Rollenwechsel durchführen
                </button>
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

// Role change and succession logic
<?php if (Auth::isBoardMember()): ?>
const currentRole = '<?php echo $user['role']; ?>';
const boardRoles = <?php echo json_encode(Auth::BOARD_ROLES); ?>;
const modal = document.getElementById('successionModal');
const newRoleSelect = document.getElementById('newRoleSelect');
const changeRoleBtn = document.getElementById('changeRoleBtn');
const successorSelect = document.getElementById('successorSelect');
const confirmSuccessionBtn = document.getElementById('confirmSuccession');
const closeModalBtn = document.getElementById('closeSuccessionModal');
const cancelSuccessionBtn = document.getElementById('cancelSuccession');

// Handle role change button click
changeRoleBtn.addEventListener('click', function() {
    const newRole = newRoleSelect.value;
    
    if (!newRole) {
        alert('Bitte wähle eine neue Rolle aus');
        return;
    }
    
    if (newRole === currentRole) {
        alert('Die gewählte Rolle ist identisch mit deiner aktuellen Rolle');
        return;
    }
    
    // Check if this is a demotion from board to non-board
    const isDemotion = boardRoles.includes(currentRole) && !boardRoles.includes(newRole);
    
    if (isDemotion) {
        // Show succession modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } else {
        // Direct role change without succession
        performRoleChange(newRole, null);
    }
});

// Close modal handlers
closeModalBtn.addEventListener('click', closeModal);
cancelSuccessionBtn.addEventListener('click', closeModal);
modal.addEventListener('click', function(e) {
    if (e.target === modal) {
        closeModal();
    }
});

function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    successorSelect.value = '';
}

// Confirm succession
confirmSuccessionBtn.addEventListener('click', function() {
    const newRole = newRoleSelect.value;
    const successorId = successorSelect.value;
    
    if (!successorId) {
        alert('Bitte wähle einen Nachfolger aus');
        return;
    }
    
    performRoleChange(newRole, successorId);
});

// Perform role change via AJAX
function performRoleChange(newRole, successorId) {
    // Disable buttons during processing
    changeRoleBtn.disabled = true;
    confirmSuccessionBtn.disabled = true;
    
    const formData = new FormData();
    formData.append('new_role', newRole);
    if (successorId) {
        formData.append('successor_id', successorId);
    }
    
    fetch('ajax_role_succession.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert(data.message);
            
            // Redirect if specified
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.reload();
            }
        } else {
            // Show error message
            alert(data.message || 'Fehler beim Rollenwechsel');
            
            // Re-enable buttons
            changeRoleBtn.disabled = false;
            confirmSuccessionBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Rollenwechsel');
        
        // Re-enable buttons
        changeRoleBtn.disabled = false;
        confirmSuccessionBtn.disabled = false;
    });
}
<?php endif; ?>

// Sync theme preference with localStorage after successful save
<?php if ($message && strpos($message, 'Design-Einstellungen') !== false): ?>
// Theme was just saved, update data-user-theme attribute and apply theme immediately
const newTheme = '<?php echo htmlspecialchars($user['theme_preference'] ?? 'auto'); ?>';
document.body.setAttribute('data-user-theme', newTheme);
localStorage.setItem('theme', newTheme);

// Apply theme immediately
if (newTheme === 'dark') {
    document.body.classList.add('dark-mode', 'dark');
} else if (newTheme === 'light') {
    document.body.classList.remove('dark-mode', 'dark');
} else { // auto
    // Check system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('dark-mode', 'dark');
    } else {
        document.body.classList.remove('dark-mode', 'dark');
    }
}
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
