<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/User.php';

if (!Auth::check() || !Auth::canManageUsers()) {
    header('Location: ../auth/login.php');
    exit;
}

// Check if user has permission for invitation management (board or higher)
$canManageInvitations = Auth::canManageUsers();

$message = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['invite_user'])) {
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'member';
        $validityHours = isset($_POST['validity_hours']) ? intval($_POST['validity_hours']) : 168; // Default 7 days
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ungültige E-Mail-Adresse';
        } else {
            $token = Auth::generateInvitationToken($email, $role, $_SESSION['user_id'], $validityHours);
            $inviteLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/pages/auth/register.php?token=' . $token;
            $message = 'Einladung erstellt! Link: ' . $inviteLink;
        }
    } else if (isset($_POST['change_role'])) {
        $userId = $_POST['user_id'] ?? 0;
        $newRole = $_POST['new_role'] ?? '';
        
        if ($userId == $_SESSION['user_id']) {
            $error = 'Du kannst Deine eigene Rolle nicht ändern';
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
            $error = 'Du kannst Dich nicht selbst löschen';
        } else if (User::delete($userId)) {
            $message = 'Benutzer erfolgreich gelöscht';
        } else {
            $error = 'Fehler beim Löschen des Benutzers';
        }
    }
}

$users = User::getAll();

// Get current user data
$currentUser = Auth::user();
$currentUserRole = $currentUser['role'] ?? '';

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
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">E-Mail</label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    class="w-full px-4 py-2 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="benutzer@beispiel.de"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rolle</label>
                <select 
                    name="role" 
                    class="w-full px-4 py-2 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                    <option value="candidate">Anwärter</option>
                    <option value="member">Mitglied</option>
                    <option value="head">Ressortleiter</option>
                    <option value="alumni">Alumni</option>
                    <option value="alumni_board">Alumni-Vorstand</option>
                    <option value="alumni_auditor">Alumni-Finanzprüfer</option>
                    <option value="board_finance">Vorstand Finanzen & Recht</option>
                    <option value="board_internal">Vorstand Intern</option>
                    <option value="board_external">Vorstand Extern</option>
                    <option value="honorary_member">Ehrenmitglied</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Token Validity</label>
                <select 
                    name="validity_hours" 
                    class="w-full px-4 py-2 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                >
                    <option value="24">24 hours</option>
                    <option value="168" selected>7 days</option>
                    <option value="720">30 days</option>
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
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">Du</span>
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
                            <option value="candidate" <?php echo ($user['role'] == 'candidate') ? 'selected' : ''; ?>>Anwärter</option>
                            <option value="member" <?php echo ($user['role'] == 'member') ? 'selected' : ''; ?>>Mitglied</option>
                            <option value="head" <?php echo ($user['role'] == 'head') ? 'selected' : ''; ?>>Ressortleiter</option>
                            <option value="alumni" <?php echo ($user['role'] == 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                            <option value="alumni_board" <?php echo ($user['role'] == 'alumni_board') ? 'selected' : ''; ?>>Alumni-Vorstand</option>
                            <option value="alumni_auditor" <?php echo ($user['role'] == 'alumni_auditor') ? 'selected' : ''; ?>>Alumni-Finanzprüfer</option>
                            <option value="board_finance" <?php echo ($user['role'] == 'board_finance') ? 'selected' : ''; ?>>Vorstand Finanzen & Recht</option>
                            <option value="board_internal" <?php echo ($user['role'] == 'board_internal') ? 'selected' : ''; ?>>Vorstand Intern</option>
                            <option value="board_external" <?php echo ($user['role'] == 'board_external') ? 'selected' : ''; ?>>Vorstand Extern</option>
                            <option value="honorary_member" <?php echo ($user['role'] == 'honorary_member') ? 'selected' : ''; ?>>Ehrenmitglied</option>
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
                        <form method="POST" class="inline" onsubmit="return confirm('Bist Du sicher, dass Du diesen Benutzer löschen möchtest?');">
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

<!-- Succession Modal -->
<div id="successionModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
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
                <p class="font-medium mb-2">Hinweis: Wenn du deine Vorstandsrolle abgibst, musst du einen Nachfolger bestimmen.</p>
                <p>Bitte wähle ein Mitglied aus, das deine Vorstandsrolle übernehmen soll.</p>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nachfolger auswählen</label>
                <select 
                    id="successorSelect"
                    class="w-full px-4 py-2 bg-white border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">-- Bitte wählen --</option>
                    <?php 
                    // Get users with 'member' or 'head' role for successor selection
                    foreach ($users as $member):
                        if ($member['id'] != $_SESSION['user_id'] && in_array($member['role'], ['member', 'head'])):
                    ?>
                    <option value="<?php echo $member['id']; ?>">
                        <?php echo htmlspecialchars($member['email']); ?> (<?php echo htmlspecialchars(translateRole($member['role'])); ?>)
                    </option>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button id="cancelSuccession" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Abbrechen
                </button>
                <button id="confirmSuccession" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <i class="fas fa-check mr-2"></i>Rollenwechsel durchführen
                </button>
            </div>
        </div>
    </div>
</div>

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
    // Define board roles and current user's role
    const boardRoles = <?php echo json_encode(Auth::BOARD_ROLES); ?>;
    const currentUserRole = '<?php echo $currentUserRole; ?>';
    const currentUserId = <?php echo $_SESSION['user_id']; ?>;
    
    const roleSelects = document.querySelectorAll('.role-select');
    const modal = document.getElementById('successionModal');
    const successorSelect = document.getElementById('successorSelect');
    const confirmSuccessionBtn = document.getElementById('confirmSuccession');
    const closeModalBtn = document.getElementById('closeSuccessionModal');
    const cancelSuccessionBtn = document.getElementById('cancelSuccession');
    
    // Store for pending role change
    let pendingRoleChange = null;
    
    roleSelects.forEach(select => {
        // Store original value when attaching event listener
        const originalValue = select.value;
        
        select.addEventListener('change', function() {
            const userId = parseInt(this.getAttribute('data-user-id'));
            const newRole = this.value;
            
            // Check if current logged-in user (board member) is trying to demote themselves
            const isCurrentUser = (userId === currentUserId);
            const isCurrentUserBoard = boardRoles.includes(currentUserRole);
            const isTargetRoleNonBoard = (newRole === 'member' || newRole === 'alumni');
            
            if (isCurrentUser && isCurrentUserBoard && isTargetRoleNonBoard) {
                // Show succession modal
                pendingRoleChange = {
                    userId: userId,
                    newRole: newRole,
                    selectElement: this
                };
                
                modal.style.display = 'flex';
                successorSelect.value = '';
                return;
            }
            
            // Disable select while processing
            this.disabled = true;
            
            // Send AJAX request
            performRoleChange(userId, newRole, null, this, originalValue);
        });
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
        modal.style.display = 'none';
        
        // Revert the select if pending change exists
        if (pendingRoleChange) {
            const originalOptions = pendingRoleChange.selectElement.querySelectorAll('option');
            originalOptions.forEach(opt => {
                if (opt.hasAttribute('selected')) {
                    pendingRoleChange.selectElement.value = opt.value;
                }
            });
            pendingRoleChange = null;
        }
        
        successorSelect.value = '';
    }
    
    // Confirm succession
    confirmSuccessionBtn.addEventListener('click', function() {
        const successorId = successorSelect.value;
        
        if (!successorId) {
            alert('Bitte wähle einen Nachfolger aus');
            return;
        }
        
        if (pendingRoleChange) {
            pendingRoleChange.selectElement.disabled = true;
            confirmSuccessionBtn.disabled = true;
            
            performRoleChange(
                pendingRoleChange.userId, 
                pendingRoleChange.newRole, 
                successorId, 
                pendingRoleChange.selectElement,
                currentUserRole  // Use current role as original value for revert
            );
        }
    });
    
    function performRoleChange(userId, newRole, successorId, selectElement, originalValue) {
        let body = 'user_id=' + encodeURIComponent(userId) + '&new_role=' + encodeURIComponent(newRole);
        if (successorId) {
            body += '&successor_id=' + encodeURIComponent(successorId);
        }
        
        fetch('ajax_update_role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showMessage(data.message, 'success');
                
                // Close modal if it was open
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                    pendingRoleChange = null;
                }
                
                // If this was a self-demotion, reload the page after a short delay
                if (userId === currentUserId) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Update the selected option
                    selectElement.querySelectorAll('option').forEach(opt => {
                        opt.removeAttribute('selected');
                        if (opt.value === newRole) {
                            opt.setAttribute('selected', 'selected');
                        }
                    });
                }
            } else {
                // Show error and revert selection
                showMessage(data.message, 'error');
                selectElement.value = originalValue;
                
                // Close modal on error
                if (modal.style.display === 'flex') {
                    modal.style.display = 'none';
                    pendingRoleChange = null;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Fehler beim Ändern der Rolle', 'error');
            selectElement.value = originalValue;
            
            // Close modal on error
            if (modal.style.display === 'flex') {
                modal.style.display = 'none';
                pendingRoleChange = null;
            }
        })
        .finally(() => {
            // Re-enable select and button
            selectElement.disabled = false;
            confirmSuccessionBtn.disabled = false;
        });
    }
    
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
