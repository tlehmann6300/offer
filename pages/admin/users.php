<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/User.php';

AuthHandler::startSession();

if (!AuthHandler::isAuthenticated() || !AuthHandler::hasPermission('admin')) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user has permission for invitation management (board or higher)
$canManageInvitations = AuthHandler::hasPermission('board');

$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['invite_user'])) {
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'member';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ungültige E-Mail-Adresse';
        } else {
            $token = AuthHandler::generateInvitationToken($email, $role, $_SESSION['user_id']);
            $inviteLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/pages/auth/register.php?token=' . $token;
            $message = 'Einladung erstellt! Link: ' . $inviteLink;
        }
    } else if (isset($_POST['change_role'])) {
        $userId = $_POST['user_id'] ?? 0;
        $newRole = $_POST['new_role'] ?? '';
        
        if ($userId == $_SESSION['user_id']) {
            $error = 'Sie können Ihre eigene Rolle nicht ändern';
        } else if (User::update($userId, ['role' => $newRole])) {
            $message = 'Rolle erfolgreich geändert';
        } else {
            $error = 'Fehler beim Ändern der Rolle';
        }
    } else if (isset($_POST['toggle_alumni_validation'])) {
        $userId = $_POST['user_id'] ?? 0;
        $isValidated = $_POST['is_validated'] ?? 0;
        
        if (User::update($userId, ['is_alumni_validated' => $isValidated])) {
            $message = $isValidated ? 'Alumni-Profil freigegeben' : 'Alumni-Profil gesperrt';
        } else {
            $error = 'Fehler beim Ändern des Alumni-Status';
        }
    } else if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'] ?? 0;
        
        if ($userId == $_SESSION['user_id']) {
            $error = 'Sie können sich nicht selbst löschen';
        } else if (User::delete($userId)) {
            $message = 'Benutzer erfolgreich gelöscht';
        } else {
            $error = 'Fehler beim Löschen des Benutzers';
        }
    }
}

$users = User::getAll();

$title = 'Benutzerverwaltung - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-users text-purple-600 mr-2"></i>
        Benutzerverwaltung
    </h1>
    <p class="text-gray-600"><?php echo count($users); ?> Benutzer gesamt</p>
</div>

<!-- Tab Navigation -->
<div class="mb-6">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button 
                class="tab-button active border-purple-500 text-purple-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                data-tab="users"
            >
                <i class="fas fa-users mr-2"></i>
                Benutzerliste
            </button>
            <?php if ($canManageInvitations): ?>
            <button 
                class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                data-tab="invitations"
            >
                <i class="fas fa-envelope mr-2"></i>
                Einladungen
            </button>
            <?php endif; ?>
        </nav>
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

<!-- Tab Content: Users -->
<div id="tab-users" class="tab-content">
    <!-- Invite User -->
    <div class="card p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-user-plus text-green-600 mr-2"></i>
            Neuen Benutzer einladen
        </h2>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">E-Mail</label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="benutzer@beispiel.de"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Rolle</label>
                <select 
                    name="role" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <option value="member">Mitglied</option>
                    <option value="alumni">Alumni</option>
                    <option value="manager">Ressortleiter</option>
                    <option value="alumni_board">Alumni-Vorstand</option>
                    <option value="board">Vorstand</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" name="invite_user" class="w-full btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i>Einladung senden
                </button>
            </div>
        </form>
    </div>

    <!-- Users List -->
    <div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Benutzer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rolle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">2FA / Validierung</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letzter Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Sie</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500">ID: <?php echo $user['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <select 
                            data-user-id="<?php echo $user['id']; ?>"
                            class="role-select px-3 py-1 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                            <?php echo ($user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>
                        >
                            <option value="member" <?php echo ($user['role'] == 'member') ? 'selected' : ''; ?>>Mitglied</option>
                            <option value="alumni" <?php echo ($user['role'] == 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                            <option value="manager" <?php echo ($user['role'] == 'manager') ? 'selected' : ''; ?>>Ressortleiter</option>
                            <option value="alumni_board" <?php echo ($user['role'] == 'alumni_board') ? 'selected' : ''; ?>>Alumni-Vorstand</option>
                            <option value="board" <?php echo ($user['role'] == 'board') ? 'selected' : ''; ?>>Vorstand</option>
                            <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <?php if ($user['tfa_enabled']): ?>
                            <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full inline-flex items-center">
                                <i class="fas fa-shield-alt mr-1"></i>2FA
                            </span>
                            <?php endif; ?>
                            <?php if ($user['role'] == 'alumni'): ?>
                                <?php if ($user['is_alumni_validated']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_validated" value="0">
                                    <button type="submit" name="toggle_alumni_validation" class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full inline-flex items-center hover:bg-green-200">
                                        <i class="fas fa-check-circle mr-1"></i>Verifiziert
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_validated" value="1">
                                    <button type="submit" name="toggle_alumni_validation" class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full inline-flex items-center hover:bg-yellow-200">
                                        <i class="fas fa-clock mr-1"></i>Ausstehend
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Sind Sie sicher, dass Sie diesen Benutzer löschen möchten?');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div>
</div>
<!-- End Tab Content: Users -->

<!-- Tab Content: Invitations -->
<?php if ($canManageInvitations): ?>
<div id="tab-invitations" class="tab-content hidden">
    <?php include __DIR__ . '/../../templates/components/invitation_management.php'; ?>
</div>
<?php endif; ?>
<!-- End Tab Content: Invitations -->

<script>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update button styles
            tabButtons.forEach(btn => {
                btn.classList.remove('active', 'border-purple-500', 'text-purple-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('active', 'border-purple-500', 'text-purple-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Update content visibility
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById('tab-' + targetTab).classList.remove('hidden');
        });
    });
});
</script>

<script>
// AJAX role change handler
document.addEventListener('DOMContentLoaded', function() {
    const roleSelects = document.querySelectorAll('.role-select');
    
    roleSelects.forEach(select => {
        // Store original value when attaching event listener
        const originalValue = select.value;
        
        select.addEventListener('change', function() {
            const userId = this.getAttribute('data-user-id');
            const newRole = this.value;
            
            // Disable select while processing
            this.disabled = true;
            
            // Send AJAX request
            fetch('ajax_update_role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + encodeURIComponent(userId) + '&new_role=' + encodeURIComponent(newRole)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(data.message, 'success');
                    // Update the selected option
                    this.querySelectorAll('option').forEach(opt => {
                        opt.removeAttribute('selected');
                        if (opt.value === newRole) {
                            opt.setAttribute('selected', 'selected');
                        }
                    });
                } else {
                    // Show error and revert selection
                    showMessage(data.message, 'error');
                    this.value = originalValue;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Fehler beim Ändern der Rolle', 'error');
                this.value = originalValue;
            })
            .finally(() => {
                // Re-enable select
                this.disabled = false;
            });
        });
    });
    
    // Function to show messages
    function showMessage(message, type) {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.ajax-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create new message element
        const messageDiv = document.createElement('div');
        messageDiv.className = 'ajax-message mb-6 p-4 rounded-lg ' + 
            (type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700');
        messageDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check' : 'exclamation') + '-circle mr-2"></i>' + message;
        
        // Insert at the top of main content
        const mainContent = document.querySelector('main > div:first-child') || document.querySelector('main');
        mainContent.insertBefore(messageDiv, mainContent.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
