<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../includes/models/UsefulLink.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Access Control
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? '';

// Only allowed roles can access this page
$allowedRoles = ['board_finance', 'board_internal', 'board_external', 'alumni_board', 'alumni_auditor'];
if (!in_array($userRole, $allowedRoles)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Edit permission: only board roles
$canEdit = in_array($userRole, ['board_finance', 'board_internal', 'board_external']);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');

    // Add link
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title !== '' && $url !== '') {
            // Validate URL format
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                UsefulLink::create([
                    'title' => $title,
                    'url' => $url,
                    'description' => $description
                ], $user['id']);
                $_SESSION['success_message'] = 'Link erfolgreich hinzugefügt.';
            } else {
                $_SESSION['error_message'] = 'Bitte gib eine gültige URL ein.';
            }
        } else {
            $_SESSION['error_message'] = 'Titel und URL sind Pflichtfelder.';
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Delete link
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['link_id'] ?? 0);
        if ($id > 0) {
            UsefulLink::delete($id);
            $_SESSION['success_message'] = 'Link erfolgreich gelöscht.';
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Fetch all links
$links = UsefulLink::getAll();

$title = 'Nützliche Links - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php unset($_SESSION['success_message']); endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error_message']); ?>
    </div>
    <?php unset($_SESSION['error_message']); endif; ?>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100 mb-2">
            <i class="fas fa-link mr-3 text-blue-600 dark:text-blue-400"></i>
            Nützliche Links
        </h1>
        <p class="text-gray-600 dark:text-gray-300">Hilfreiche Links und Ressourcen für das Team</p>
    </div>

    <!-- Add Link Form (only for board roles) -->
    <?php if ($canEdit): ?>
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
            <i class="fas fa-plus-circle mr-2 text-blue-600 dark:text-blue-400"></i>
            Neuen Link hinzufügen
        </h2>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Titel <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="title" name="title" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Link-Titel">
                </div>
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        URL <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="url" name="url" required
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="https://...">
                </div>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Beschreibung
                </label>
                <textarea id="description" name="description" rows="2"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="Kurze Beschreibung des Links..."></textarea>
            </div>
            <button type="submit"
                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                <i class="fas fa-plus mr-2"></i>
                Link hinzufügen
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Links Grid -->
    <?php if (empty($links)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-link text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-2">Keine Links vorhanden</p>
            <p class="text-gray-500 dark:text-gray-400">Es wurden noch keine nützlichen Links hinzugefügt.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($links as $link): ?>
            <div class="card p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-200">
                <div>
                    <div class="flex items-start justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            <i class="fas fa-link mr-2 text-blue-500 dark:text-blue-400"></i>
                            <?php echo htmlspecialchars($link['title']); ?>
                        </h3>
                    </div>
                    <?php if (!empty($link['description'])): ?>
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                        <?php echo htmlspecialchars($link['description']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white rounded-lg text-sm font-medium hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Öffnen
                    </a>
                    <?php if ($canEdit): ?>
                    <form method="POST" action="" class="inline" onsubmit="return confirm('Diesen Link wirklich löschen?');">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="link_id" value="<?php echo intval($link['id']); ?>">
                        <button type="submit"
                                class="inline-flex items-center px-3 py-2 bg-red-600 dark:bg-red-700 text-white rounded-lg text-sm font-medium hover:bg-red-700 dark:hover:bg-red-600 transition-colors">
                            <i class="fas fa-trash-alt mr-1"></i>
                            Löschen
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                    Hinzugefügt am <?php echo date('d.m.Y', strtotime($link['created_at'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
