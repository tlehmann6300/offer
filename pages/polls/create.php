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
if (!Auth::hasRole(['head', 'board', 'vorstand_intern', 'vorstand_extern', 'vorstand_finanzen_recht'])) {
    header('Location: ../dashboard/index.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_poll'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $options = $_POST['options'] ?? [];
    $targetGroups = $_POST['target_groups'] ?? [];
    
    // Validation
    if (empty($title)) {
        $errorMessage = 'Bitte geben Sie einen Titel ein.';
    } elseif (empty($endDate)) {
        $errorMessage = 'Bitte geben Sie ein Enddatum an.';
    } elseif (strtotime($endDate) <= time()) {
        $errorMessage = 'Das Enddatum muss in der Zukunft liegen.';
    } elseif (count($options) < 2) {
        $errorMessage = 'Bitte geben Sie mindestens 2 Antwortmöglichkeiten an.';
    } elseif (empty($targetGroups)) {
        $errorMessage = 'Bitte wählen Sie mindestens eine Zielgruppe aus.';
    } else {
        // Filter out empty options
        $options = array_filter($options, function($option) {
            return !empty(trim($option));
        });
        
        if (count($options) < 2) {
            $errorMessage = 'Bitte geben Sie mindestens 2 nicht-leere Antwortmöglichkeiten an.';
        } else {
            try {
                $db = Database::getContentDB();
                $db->beginTransaction();
                
                // Insert poll
                $stmt = $db->prepare("
                    INSERT INTO polls (title, description, created_by, end_date, target_groups, is_active)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $targetGroupsJson = json_encode($targetGroups);
                $stmt->execute([$title, $description, $user['id'], $endDate, $targetGroupsJson]);
                $pollId = $db->lastInsertId();
                
                // Insert poll options
                $stmt = $db->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
                foreach ($options as $option) {
                    $optionText = trim($option);
                    if (!empty($optionText)) {
                        $stmt->execute([$pollId, $optionText]);
                    }
                }
                
                $db->commit();
                
                // Redirect to polls list
                header('Location: ' . asset('pages/polls/index.php'));
                exit;
                
            } catch (Exception $e) {
                $db->rollBack();
                error_log('Error creating poll: ' . $e->getMessage());
                $errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            }
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

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Enddatum <span class="text-red-500 dark:text-red-400">*</span>
                </label>
                <input 
                    type="datetime-local" 
                    id="end_date" 
                    name="end_date" 
                    required
                    value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                >
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

            <!-- Poll Options -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Antwortmöglichkeiten <span class="text-red-500 dark:text-red-400">*</span>
                </label>
                <div id="optionsContainer" class="space-y-3">
                    <!-- Initial 2 options -->
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            name="options[]" 
                            required
                            maxlength="255"
                            value="<?php echo htmlspecialchars($_POST['options'][0] ?? ''); ?>"
                            class="flex-1 px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                            placeholder="Option 1"
                        >
                    </div>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            name="options[]" 
                            required
                            maxlength="255"
                            value="<?php echo htmlspecialchars($_POST['options'][1] ?? ''); ?>"
                            class="flex-1 px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                            placeholder="Option 2"
                        >
                    </div>
                </div>
                <button 
                    type="button"
                    id="addOptionBtn"
                    class="mt-3 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                >
                    <i class="fas fa-plus mr-2"></i>Weitere Option hinzufügen
                </button>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800 dark:text-blue-300">
                        <p class="font-semibold mb-1">Hinweis:</p>
                        <p>Die Umfrage wird automatisch aktiv und die ausgewählten Zielgruppen können abstimmen.</p>
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

<script>
// Set minimum datetime to current time
document.addEventListener('DOMContentLoaded', function() {
    const endDateInput = document.getElementById('end_date');
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    endDateInput.min = now.toISOString().slice(0, 16);
});

// Dynamic option adding
document.getElementById('addOptionBtn').addEventListener('click', function() {
    const container = document.getElementById('optionsContainer');
    const optionCount = container.children.length + 1;
    
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input 
            type="text" 
            name="options[]" 
            maxlength="255"
            class="flex-1 px-4 py-3 bg-white border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white"
            placeholder="Option ${optionCount}"
        >
        <button 
            type="button"
            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors remove-option-btn"
        >
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(div);
    
    // Add remove functionality
    div.querySelector('.remove-option-btn').addEventListener('click', function() {
        div.remove();
    });
});

// Add remove functionality to dynamically added options
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-option-btn') || e.target.closest('.remove-option-btn')) {
        const btn = e.target.classList.contains('remove-option-btn') ? e.target : e.target.closest('.remove-option-btn');
        btn.parentElement.remove();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
