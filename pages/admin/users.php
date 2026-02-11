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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                <i class="fas fa-users text-purple-600 dark:text-purple-400 mr-2"></i>
                Benutzerverwaltung
            </h1>
            <p class="text-gray-600 dark:text-gray-300"><?php echo count($users); ?> Benutzer gesamt</p>
        </div>
        <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 px-4 py-2 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
            <i class="fas fa-chart-line text-green-600 dark:text-green-400 text-xl"></i>
            <div class="text-sm">
                <div class="font-semibold text-gray-900 dark:text-gray-100"><?php 
                    $activeToday = 0;
                    foreach ($users as $u) {
                        if ($u['last_login'] && strtotime($u['last_login']) > strtotime('-24 hours')) {
                            $activeToday++;
                        }
                    }
                    echo $activeToday;
                ?></div>
                <div class="text-gray-500 dark:text-gray-400">Aktiv heute</div>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="mb-6">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button 
                class="tab-button active border-purple-500 text-purple-600 dark:text-purple-400 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                data-tab="users"
            >
                <i class="fas fa-users mr-2"></i>
                Benutzerliste
            </button>
            <?php if ($canManageInvitations): ?>
            <button 
                class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
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
<div class="mb-6 p-4 bg-green-100 dark:bg-green-900/50 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 dark:bg-red-900/50 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- Tab Content: Users -->
<div id="tab-users" class="tab-content">
    <!-- Invite User -->
    <div class="card p-6 mb-6 bg-gradient-to-r from-white to-green-50 dark:from-gray-800 dark:to-green-900/20">
        <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
            <i class="fas fa-user-plus text-green-600 dark:text-green-400 mr-2"></i>
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
                    <?php foreach (Auth::VALID_ROLES as $role): ?>
                    <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(translateRole($role)); ?></option>
                    <?php endforeach; ?>
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
        <!-- Search and Filter Bar -->
        <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="flex flex-col md:flex-row gap-4 mb-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search mr-1"></i>Suche
                    </label>
                    <input 
                        type="text" 
                        id="userSearch" 
                        placeholder="Nach E-Mail oder ID suchen..." 
                        class="w-full px-4 py-2 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    >
                </div>
                <div class="md:w-48">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-filter mr-1"></i>Filter nach Rolle
                    </label>
                    <select 
                        id="roleFilter" 
                        class="w-full px-4 py-2 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">Alle Rollen</option>
                        <?php foreach (Auth::VALID_ROLES as $role): ?>
                        <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars(translateRole($role)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:w-48">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-sort mr-1"></i>Sortierung
                    </label>
                    <select 
                        id="sortBy" 
                        class="w-full px-4 py-2 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="email">E-Mail (A-Z)</option>
                        <option value="email-desc">E-Mail (Z-A)</option>
                        <option value="login">Letzter Login (neu)</option>
                        <option value="login-old">Letzter Login (alt)</option>
                        <option value="id">ID (aufsteigend)</option>
                        <option value="id-desc">ID (absteigend)</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-300">
                    <span id="visibleCount"><?php echo count($users); ?></span> von 
                    <span id="totalCount"><?php echo count($users); ?></span> Benutzern
                </div>
                <button 
                    id="exportUsers" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium"
                >
                    <i class="fas fa-download mr-2"></i>Export CSV
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full" id="usersTable">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Benutzer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rolle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">2FA / Validierung</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Letzter Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($users as $user): ?>
                <tr class="user-row hover:bg-gray-50 dark:hover:bg-gray-700" 
                    data-email="<?php echo htmlspecialchars(strtolower($user['email'])); ?>"
                    data-role="<?php echo htmlspecialchars($user['role']); ?>"
                    data-id="<?php echo $user['id']; ?>"
                    data-login="<?php echo $user['last_login'] ? strtotime($user['last_login']) : 0; ?>">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-purple-600 dark:text-purple-400"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="ml-2 px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full">Du</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">ID: <?php echo $user['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <select 
                            data-user-id="<?php echo $user['id']; ?>"
                            class="role-select px-3 py-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        >
                            <?php foreach (Auth::VALID_ROLES as $role): ?>
                            <option value="<?php echo htmlspecialchars($role); ?>" <?php echo ($user['role'] == $role) ? 'selected' : ''; ?>><?php echo htmlspecialchars(translateRole($role)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <?php if ($user['tfa_enabled']): ?>
                            <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full inline-flex items-center">
                                <i class="fas fa-shield-alt mr-1"></i>2FA
                            </span>
                            <?php endif; ?>
                            <?php if ($user['role'] == 'alumni'): ?>
                                <?php if ($user['is_alumni_validated']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_validated" value="0">
                                    <button type="submit" name="toggle_alumni_validation" class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300 rounded-full inline-flex items-center hover:bg-green-200 dark:hover:bg-green-900">
                                        <i class="fas fa-check-circle mr-1"></i>Verifiziert
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="is_validated" value="1">
                                    <button type="submit" name="toggle_alumni_validation" class="px-2 py-1 text-xs bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 rounded-full inline-flex items-center hover:bg-yellow-200 dark:hover:bg-yellow-900">
                                        <i class="fas fa-clock mr-1"></i>Ausstehend
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                        <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" class="inline" onsubmit="return confirm('Bist Du sicher, dass Du diesen Benutzer löschen möchtest?');">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
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
                        if ((int)$member['id'] !== (int)$currentUser['id'] && in_array($member['role'], ['member', 'head'], true)):
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
// Search, Filter, and Sort functionality
document.addEventListener('DOMContentLoaded', function() {
    const userSearch = document.getElementById('userSearch');
    const roleFilter = document.getElementById('roleFilter');
    const sortBy = document.getElementById('sortBy');
    const exportBtn = document.getElementById('exportUsers');
    const userRows = document.querySelectorAll('.user-row');
    const visibleCount = document.getElementById('visibleCount');
    const totalCount = document.getElementById('totalCount');
    
    function filterAndSortUsers() {
        const searchTerm = userSearch.value.toLowerCase();
        const selectedRole = roleFilter.value;
        const sortOption = sortBy.value;
        
        // Convert NodeList to Array for sorting
        let rowsArray = Array.from(userRows);
        
        // Apply search and role filter
        let visibleRows = rowsArray.filter(row => {
            const email = row.getAttribute('data-email');
            const id = row.getAttribute('data-id');
            const role = row.getAttribute('data-role');
            
            const matchesSearch = email.includes(searchTerm) || id.toString().includes(searchTerm);
            const matchesRole = !selectedRole || role === selectedRole;
            
            return matchesSearch && matchesRole;
        });
        
        // Apply sorting
        visibleRows.sort((a, b) => {
            switch(sortOption) {
                case 'email':
                    return a.getAttribute('data-email').localeCompare(b.getAttribute('data-email'));
                case 'email-desc':
                    return b.getAttribute('data-email').localeCompare(a.getAttribute('data-email'));
                case 'login':
                    return parseInt(b.getAttribute('data-login')) - parseInt(a.getAttribute('data-login'));
                case 'login-old':
                    return parseInt(a.getAttribute('data-login')) - parseInt(b.getAttribute('data-login'));
                case 'id':
                    return parseInt(a.getAttribute('data-id')) - parseInt(b.getAttribute('data-id'));
                case 'id-desc':
                    return parseInt(b.getAttribute('data-id')) - parseInt(a.getAttribute('data-id'));
                default:
                    return 0;
            }
        });
        
        // Hide all rows
        userRows.forEach(row => {
            row.style.display = 'none';
        });
        
        // Show and reorder visible rows
        const tbody = document.querySelector('#usersTable tbody');
        visibleRows.forEach(row => {
            row.style.display = '';
            tbody.appendChild(row); // Reorder by appending
        });
        
        // Update counter
        visibleCount.textContent = visibleRows.length;
    }
    
    // Event listeners
    userSearch.addEventListener('input', filterAndSortUsers);
    roleFilter.addEventListener('change', filterAndSortUsers);
    sortBy.addEventListener('change', filterAndSortUsers);
    
    // Export to CSV functionality
    exportBtn.addEventListener('click', function() {
        const visibleRows = Array.from(userRows).filter(row => row.style.display !== 'none');
        
        let csv = 'ID,E-Mail,Rolle,2FA Aktiviert,Alumni Verifiziert,Letzter Login\n';
        
        visibleRows.forEach(row => {
            const id = row.getAttribute('data-id');
            const email = row.getAttribute('data-email');
            const role = row.getAttribute('data-role');
            
            // Get additional info from row cells
            const cells = row.querySelectorAll('td');
            const tfaBadge = cells[2].querySelector('.fa-shield-alt');
            const tfa = tfaBadge ? 'Ja' : 'Nein';
            
            const verifBadge = cells[2].querySelector('.fa-check-circle');
            const verif = verifBadge ? 'Ja' : (cells[2].querySelector('.fa-clock') ? 'Nein' : 'N/A');
            
            const login = cells[3].textContent.trim();
            
            csv += `${id},"${email}","${role}","${tfa}","${verif}","${login}"\n`;
        });
        
        // Create download link
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const dateStr = new Date().toLocaleDateString('de-DE').replace(/\./g, '-');
        
        link.setAttribute('href', url);
        link.setAttribute('download', 'benutzer_export_' + dateStr + '.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

<script>
// AJAX role change handler
document.addEventListener('DOMContentLoaded', function() {
    // Define board roles and current user's role
    const boardRoles = <?php echo json_encode(Auth::BOARD_ROLES); ?>;
    const currentUserRole = '<?php echo $currentUserRole; ?>';
    const currentUserId = <?php echo $currentUser['id']; ?>;
    
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
            const isCurrentUser = (userId === parseInt(currentUserId, 10));
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
                
                // If this was a self-demotion, redirect to dashboard after a short delay
                if (userId === currentUserId) {
                    setTimeout(() => {
                        window.location.href = '../dashboard/index.php';
                    }, 1000);
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
        
        // Create icon
        const icon = document.createElement('i');
        icon.className = 'fas fa-' + (type === 'success' ? 'check' : 'exclamation') + '-circle mr-2';
        
        // Create text node for message (safe from XSS)
        const messageText = document.createTextNode(message);
        
        // Append elements
        messageDiv.appendChild(icon);
        messageDiv.appendChild(messageText);
        
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
