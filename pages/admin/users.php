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
        <div class="flex items-center space-x-4">
            <a href="bulk_invite.php" class="btn-primary flex items-center">
                <i class="fas fa-user-plus mr-2"></i>
                Masseneinladung
            </a>
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
    <!-- Info about Microsoft Only System -->
    <div class="card p-6 mb-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-l-4 border-blue-500 dark:border-blue-400">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-2xl mt-1"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Microsoft Only Authentifizierung</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-2">
                    Benutzer werden ausschließlich über Microsoft Entra ID verwaltet. Neue Benutzer können nur über Einladungen hinzugefügt werden.
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Verwenden Sie <a href="bulk_invite.php" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">Masseneinladung</a> zum Einladen neuer Benutzer.
                </p>
            </div>
        </div>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Profilbild</th>
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
                        <?php 
                        $profilePhotoPath = __DIR__ . '/../../uploads/profile_photos/user_' . $user['id'] . '.jpg';
                        $profilePhotoUrl = '/uploads/profile_photos/user_' . $user['id'] . '.jpg';
                        if (file_exists($profilePhotoPath)): 
                        ?>
                            <img src="<?php echo htmlspecialchars($profilePhotoUrl); ?>" 
                                 alt="Profilbild" 
                                 class="h-10 w-10 rounded-full object-cover border-2 border-purple-200 dark:border-purple-700">
                        <?php else: ?>
                            <div class="flex-shrink-0 h-10 w-10 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-purple-600 dark:text-purple-400"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
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
                        <div class="flex flex-col space-y-1">
                            <span class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg font-medium">
                                <?php echo htmlspecialchars(translateRole($user['role'])); ?>
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 italic">
                                <i class="fas fa-info-circle mr-1"></i>Rolle in Microsoft Entra zuweisen
                            </span>
                        </div>
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
// Role change functionality removed - roles are now managed via Microsoft Entra
// Users should use the bulk_invite.php tool to assign roles during invitation
document.addEventListener('DOMContentLoaded', function() {
    // Note: Role dropdowns have been replaced with read-only display
    console.log('User management running in Microsoft Only mode');
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
