<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../includes/models/Event.php';
require_once __DIR__ . '/../../includes/models/User.php';

AuthHandler::startSession();

// Only board, alumni_board, and manager can access
if (!AuthHandler::isAuthenticated() || !AuthHandler::hasPermission('manager')) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$error = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
    
    $eventId = intval($_POST['event_id'] ?? 0);
    
    try {
        Event::delete($eventId, $_SESSION['user_id']);
        $message = 'Event erfolgreich gelöscht';
    } catch (Exception $e) {
        $error = 'Fehler beim Löschen: ' . $e->getMessage();
    }
}

// Get filters
$filters = [];
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['needs_helpers']) && $_GET['needs_helpers'] !== '') {
    $filters['needs_helpers'] = $_GET['needs_helpers'] == '1';
}
if (!empty($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}
$filters['include_helpers'] = true;

// Get events
$userRole = $_SESSION['role'] ?? 'member';
$events = Event::getEvents($filters, $userRole);

$title = 'Event-Verwaltung - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
                Event-Verwaltung
            </h1>
            <p class="text-gray-600"><?php echo count($events); ?> Event(s) gefunden</p>
        </div>
        <a href="edit.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Neues Event
        </a>
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

<!-- Filter Section -->
<div class="card p-6 mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">
        <i class="fas fa-filter text-purple-600 mr-2"></i>Filter
    </h2>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">Alle</option>
                <option value="planned" <?php echo (isset($_GET['status']) && $_GET['status'] === 'planned') ? 'selected' : ''; ?>>Geplant</option>
                <option value="open" <?php echo (isset($_GET['status']) && $_GET['status'] === 'open') ? 'selected' : ''; ?>>Offen</option>
                <option value="running" <?php echo (isset($_GET['status']) && $_GET['status'] === 'running') ? 'selected' : ''; ?>>Laufend</option>
                <option value="closed" <?php echo (isset($_GET['status']) && $_GET['status'] === 'closed') ? 'selected' : ''; ?>>Geschlossen</option>
                <option value="past" <?php echo (isset($_GET['status']) && $_GET['status'] === 'past') ? 'selected' : ''; ?>>Vergangen</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Helfer benötigt</label>
            <select name="needs_helpers" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">Alle</option>
                <option value="1" <?php echo (isset($_GET['needs_helpers']) && $_GET['needs_helpers'] === '1') ? 'selected' : ''; ?>>Ja</option>
                <option value="0" <?php echo (isset($_GET['needs_helpers']) && $_GET['needs_helpers'] === '0') ? 'selected' : ''; ?>>Nein</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Von Datum</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Bis Datum</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <div class="md:col-span-2 lg:col-span-4 flex justify-end space-x-2">
            <a href="manage.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times mr-2"></i>Zurücksetzen
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-search mr-2"></i>Filtern
            </button>
        </div>
    </form>
</div>

<!-- Events Grid -->
<?php if (empty($events)): ?>
<div class="card p-12 text-center">
    <i class="fas fa-calendar-times text-gray-400 text-6xl mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-600 mb-2">Keine Events gefunden</h3>
    <p class="text-gray-500 mb-6">Es wurden keine Events mit den ausgewählten Filtern gefunden.</p>
    <a href="edit.php" class="btn-primary inline-block">
        <i class="fas fa-plus mr-2"></i>Erstes Event erstellen
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($events as $event): ?>
    <div class="card p-6 hover:shadow-lg transition">
        <!-- Status Badge -->
        <div class="flex items-start justify-between mb-4">
            <span class="px-3 py-1 text-xs font-semibold rounded-full
                <?php 
                switch($event['status']) {
                    case 'planned': echo 'bg-blue-100 text-blue-800'; break;
                    case 'open': echo 'bg-green-100 text-green-800'; break;
                    case 'running': echo 'bg-yellow-100 text-yellow-800'; break;
                    case 'closed': echo 'bg-gray-100 text-gray-800'; break;
                    case 'past': echo 'bg-red-100 text-red-800'; break;
                }
                ?>">
                <?php 
                switch($event['status']) {
                    case 'planned': echo 'Geplant'; break;
                    case 'open': echo 'Offen'; break;
                    case 'running': echo 'Laufend'; break;
                    case 'closed': echo 'Geschlossen'; break;
                    case 'past': echo 'Vergangen'; break;
                }
                ?>
            </span>
            <?php if ($event['needs_helpers']): ?>
            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                <i class="fas fa-hands-helping"></i> Helfer
            </span>
            <?php endif; ?>
        </div>

        <!-- Title -->
        <h3 class="text-xl font-bold text-gray-800 mb-2">
            <?php echo htmlspecialchars($event['title']); ?>
        </h3>

        <!-- Location and Time -->
        <div class="space-y-2 mb-4 text-sm text-gray-600">
            <?php if ($event['location']): ?>
            <div class="flex items-center">
                <i class="fas fa-map-marker-alt w-5 text-purple-600"></i>
                <span><?php echo htmlspecialchars($event['location']); ?></span>
            </div>
            <?php endif; ?>
            <div class="flex items-center">
                <i class="fas fa-clock w-5 text-purple-600"></i>
                <span><?php echo date('d.m.Y H:i', strtotime($event['start_time'])); ?></span>
            </div>
            <?php if ($event['is_external']): ?>
            <div class="flex items-center">
                <i class="fas fa-external-link-alt w-5 text-purple-600"></i>
                <span>Externes Event</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Helper Info -->
        <?php if ($event['needs_helpers'] && !empty($event['helper_types'])): ?>
        <div class="mb-4 p-3 bg-purple-50 rounded-lg">
            <div class="text-sm text-gray-700">
                <strong><?php echo count($event['helper_types']); ?></strong> Helfer-Typ(en)
            </div>
        </div>
        <?php endif; ?>

        <!-- Lock Status -->
        <?php 
        $lockInfo = Event::checkLock($event['id'], $_SESSION['user_id']);
        if ($lockInfo['is_locked']): 
            $lockedUser = User::getById($lockInfo['locked_by']);
        ?>
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center text-sm text-yellow-800">
                <i class="fas fa-lock mr-2"></i>
                <span>Gesperrt von <?php echo htmlspecialchars($lockedUser['first_name'] ?? 'Benutzer'); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="flex space-x-2">
            <a href="edit.php?id=<?php echo $event['id']; ?>" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-center text-sm">
                <i class="fas fa-edit mr-1"></i>Bearbeiten
            </a>
            <button 
                onclick="confirmDelete(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars(addslashes($event['title'])); ?>')" 
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm"
                title="Löschen"
            >
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
            Event löschen
        </h3>
        <p class="text-gray-600 mb-6">
            Möchten Sie das Event "<span id="deleteEventName" class="font-semibold"></span>" wirklich löschen? 
            Diese Aktion kann nicht rückgängig gemacht werden.
        </p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
            <input type="hidden" name="event_id" id="deleteEventId" value="">
            <input type="hidden" name="delete_event" value="1">
            <div class="flex space-x-4">
                <button type="button" onclick="closeDeleteModal()" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Abbrechen
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i>Löschen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(eventId, eventName) {
    document.getElementById('deleteEventId').value = eventId;
    document.getElementById('deleteEventName').textContent = eventName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Close modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') {
        closeDeleteModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
