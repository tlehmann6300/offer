<?php
/**
 * Create Poll - Form to create a new poll
 * Access: head, board (all variants)
 */

require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();

// Check if user has permission (head or board roles)
if (!(Auth::isBoard() || Auth::hasRole('head'))) {
    header('Location: ../dashboard/index.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_poll'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $microsoftFormsUrl = trim($_POST['microsoft_forms_url'] ?? '');
    $targetGroups = $_POST['target_groups'] ?? [];
    $allowedRolesText = trim($_POST['allowed_roles_text'] ?? '');
    $visibleToAll = isset($_POST['visible_to_all']) ? 1 : 0;
    $isInternal = isset($_POST['is_internal']) ? 1 : 0;
    
    // Parse allowed roles from text input
    $allowedRoles = null;
    if (!empty($allowedRolesText)) {
        $decoded = json_decode($allowedRolesText, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $allowedRoles = $decoded;
        }
    }
    
    // Validation
    if (empty($title)) {
        $errorMessage = 'Bitte geben Sie einen Titel ein.';
    } elseif (empty($microsoftFormsUrl)) {
        $errorMessage = 'Bitte geben Sie die Microsoft Forms URL ein.';
    } elseif (empty($targetGroups)) {
        $errorMessage = 'Bitte wählen Sie mindestens eine Zielgruppe aus.';
    } elseif (!empty($allowedRolesText) && $allowedRoles === null) {
        $errorMessage = 'Die erlaubten Entra-Rollen müssen ein gültiges JSON-Array sein.';
    } else {
        try {
            $db = Database::getContentDB();
            
            // Insert poll with Microsoft Forms URL and new fields
            $stmt = $db->prepare("
                INSERT INTO polls (title, description, created_by, microsoft_forms_url, target_groups, 
                                   allowed_roles, visible_to_all, is_internal, is_active, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            $targetGroupsJson = json_encode($targetGroups);
            $allowedRolesJson = $allowedRoles ? json_encode($allowedRoles) : null;
            $stmt->execute([
                $title, 
                $description, 
                $user['id'], 
                $microsoftFormsUrl, 
                $targetGroupsJson,
                $allowedRolesJson,
                $visibleToAll,
                $isInternal
            ]);
            
            // Redirect to polls list
            header('Location: ' . asset('pages/polls/index.php'));
            exit;
            
        } catch (Exception $e) {
            error_log('Error creating poll: ' . $e->getMessage());
            $errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        }
    }
}

$title = 'Umfrage erstellen - IBC Intranet';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100 mb-2">
            <i class="fas fa-poll mr-3 text-blue-500"></i>
            Umfrage erstellen
        </h1>
        <p class="text-gray-600 dark:text-gray-300">Erstellen Sie eine neue Umfrage für Ihre Mitglieder</p>
    </div>

    <!-- Error Message -->
    <?php if ($errorMessage): ?>
    <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($errorMessage); ?>
    </div>
    <?php endif; ?>

    <!-- Create Poll Form -->
    <div class="card p-8">
        <form method="POST" class="space-y-6" id="pollForm">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Titel <span class="text-red-500 dark:text-red-400">*</span>
                </label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    required
                    maxlength="255"
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Z.B. Wahl des Veranstaltungsortes"
                >
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Beschreibung (optional)
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="4"
                    class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="Zusätzliche Informationen zur Umfrage..."
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <!-- Microsoft Forms URL -->
            <div>
                <label for="microsoft_forms_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Microsoft Forms URL <span class="text-red-500 dark:text-red-400">*</span>
                </label>
                <input 
                    type="url" 
                    id="microsoft_forms_url" 
                    name="microsoft_forms_url" 
                    required
                    value="<?php echo htmlspecialchars($_POST['microsoft_forms_url'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder="https://forms.office.com/Pages/ResponsePage.aspx?id=..."
                >
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Fügen Sie die Embed-URL oder die direkte URL zu Ihrem Microsoft Forms ein.
                </p>
                <p class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Hinweis:</strong> Microsoft bietet keine API zum automatischen erstellen. Bitte erstellen Sie das Formular manuell auf forms.office.com und fügen Sie hier den Einbettungs-Code ein.
                </p>
            </div>

            <!-- Target Groups -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Zielgruppen <span class="text-red-500 dark:text-red-400">*</span>
                </label>
                <div class="space-y-2">
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="target_groups[]" 
                            value="candidate"
                            <?php echo (isset($_POST['target_groups']) && in_array('candidate', $_POST['target_groups'])) ? 'checked' : ''; ?>
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Candidate</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="target_groups[]" 
                            value="alumni_board"
                            <?php echo (isset($_POST['target_groups']) && in_array('alumni_board', $_POST['target_groups'])) ? 'checked' : ''; ?>
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Alumni Board</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="target_groups[]" 
                            value="board"
                            <?php echo (isset($_POST['target_groups']) && in_array('board', $_POST['target_groups'])) ? 'checked' : ''; ?>
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Board</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="target_groups[]" 
                            value="member"
                            <?php echo (isset($_POST['target_groups']) && in_array('member', $_POST['target_groups'])) ? 'checked' : ''; ?>
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Member</span>
                    </label>
                    <label class="flex items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="target_groups[]" 
                            value="head"
                            <?php echo (isset($_POST['target_groups']) && in_array('head', $_POST['target_groups'])) ? 'checked' : ''; ?>
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <span class="ml-3 text-gray-700 dark:text-gray-300">Head</span>
                    </label>
                </div>
            </div>

            <!-- Visible to All Option -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <label class="flex items-start cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="visible_to_all" 
                        value="1"
                        <?php echo (isset($_POST['visible_to_all'])) ? 'checked' : ''; ?>
                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 mt-0.5"
                    >
                    <div class="ml-3">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Für alle sichtbar</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Wenn aktiviert, wird die Umfrage für alle Benutzer angezeigt, unabhängig von ihren Rollen.
                        </p>
                    </div>
                </label>
            </div>

            <!-- Is Internal Option -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <label class="flex items-start cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="is_internal" 
                        value="1"
                        <?php echo (!isset($_POST['create_poll']) || (isset($_POST['create_poll']) && isset($_POST['is_internal']))) ? 'checked' : ''; ?>
                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 mt-0.5"
                    >
                    <div class="ml-3">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Interne Umfrage</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Wenn aktiviert, wird die Umfrage automatisch ausgeblendet, nachdem der Benutzer abgestimmt hat. 
                            Deaktivieren Sie diese Option für externe Microsoft Forms-Umfragen, um den "Erledigt / Ausblenden"-Button anzuzeigen.
                        </p>
                    </div>
                </label>
            </div>

            <!-- Allowed Roles (Entra Roles) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Erlaubte Entra-Rollen (optional)
                </label>
                <textarea 
                    name="allowed_roles_text" 
                    rows="3"
                    class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                    placeholder='z.B.: ["IBC.Vorstand", "IBC.Mitglied"]'
                ><?php echo htmlspecialchars($_POST['allowed_roles_text'] ?? ''); ?></textarea>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Geben Sie die erlaubten Microsoft Entra-Rollen als JSON-Array ein. Leer lassen, um nur die Standard-Zielgruppen zu verwenden.
                </p>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800 dark:text-blue-300">
                        <p class="font-semibold mb-1">Hinweis:</p>
                        <p>Die Umfrage wird automatisch aktiv und die ausgewählten Zielgruppen können über Microsoft Forms teilnehmen. Die Umfrage läuft standardmäßig 30 Tage.</p>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
                <button 
                    type="submit"
                    name="create_poll"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg"
                >
                    <i class="fas fa-check mr-2"></i>
                    Umfrage erstellen
                </button>
                <a 
                    href="<?php echo asset('pages/polls/index.php'); ?>"
                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all text-center"
                >
                    Abbrechen
                </a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
