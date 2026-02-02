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

$eventId = intval($_GET['id'] ?? 0);
$isEdit = $eventId > 0;
$readOnly = false;
$lockWarning = '';
$event = null;
$history = [];

// If editing, try to acquire lock
if ($isEdit) {
    $event = Event::getById($eventId);
    if (!$event) {
        header('Location: manage.php');
        exit;
    }
    
    // Try to acquire lock
    $lockResult = Event::acquireLock($eventId, $_SESSION['user_id']);
    
    if (!$lockResult['success']) {
        $readOnly = true;
        $lockedUser = User::getById($lockResult['locked_by']);
        $lockWarning = 'Dieses Event wird gerade von ' . htmlspecialchars($lockedUser['first_name'] . ' ' . $lockedUser['last_name']) . ' bearbeitet. Sie befinden sich im Nur-Lesen-Modus.';
    }
    
    // Get history
    $history = Event::getHistory($eventId, 10);
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$readOnly) {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
    
    try {
        // Validate times
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        
        if (empty($startTime) || empty($endTime)) {
            throw new Exception('Start- und Endzeit sind erforderlich');
        }
        
        if (strtotime($startTime) >= strtotime($endTime)) {
            throw new Exception('Die Startzeit muss vor der Endzeit liegen');
        }
        
        // Prepare event data
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'location' => trim($_POST['location'] ?? ''),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'status' => $_POST['status'] ?? 'planned',
            'is_external' => isset($_POST['is_external']) ? 1 : 0,
            'external_link' => trim($_POST['external_link'] ?? ''),
            'needs_helpers' => isset($_POST['needs_helpers']) ? 1 : 0,
            'allowed_roles' => $_POST['allowed_roles'] ?? []
        ];
        
        if (empty($data['title'])) {
            throw new Exception('Titel ist erforderlich');
        }
        
        if ($isEdit) {
            // Update existing event
            Event::update($eventId, $data, $_SESSION['user_id']);
            
            // Handle helper types and slots if helpers are needed
            if ($data['needs_helpers']) {
                // Delete existing helper types and slots (will be recreated)
                $db = Database::getContentDB();
                $stmt = $db->prepare("DELETE FROM event_helper_types WHERE event_id = ?");
                $stmt->execute([$eventId]);
                
                // Process helper types from form
                $helperTypes = json_decode($_POST['helper_types_json'] ?? '[]', true);
                
                foreach ($helperTypes as $helperType) {
                    if (empty($helperType['title'])) continue;
                    
                    $helperTypeId = Event::createHelperType(
                        $eventId, 
                        $helperType['title'], 
                        $helperType['description'] ?? null,
                        $_SESSION['user_id']
                    );
                    
                    // Create slots for this helper type
                    if (!empty($helperType['slots'])) {
                        foreach ($helperType['slots'] as $slot) {
                            if (empty($slot['start_time']) || empty($slot['end_time'])) continue;
                            
                            // Validate slot is within event timeframe
                            $slotStart = strtotime($slot['start_time']);
                            $slotEnd = strtotime($slot['end_time']);
                            $eventStart = strtotime($startTime);
                            $eventEnd = strtotime($endTime);
                            
                            if ($slotStart < $eventStart || $slotEnd > $eventEnd) {
                                throw new Exception('Zeitslots müssen innerhalb des Event-Zeitraums liegen');
                            }
                            
                            if ($slotStart >= $slotEnd) {
                                throw new Exception('Slot-Startzeit muss vor der Endzeit liegen');
                            }
                            
                            Event::createSlot(
                                $helperTypeId,
                                $slot['start_time'],
                                $slot['end_time'],
                                intval($slot['quantity'] ?? 1),
                                $_SESSION['user_id'],
                                $eventId
                            );
                        }
                    }
                }
            }
            
            $message = 'Event erfolgreich aktualisiert';
            
            // Release lock and reload
            Event::releaseLock($eventId, $_SESSION['user_id']);
            header('Location: edit.php?id=' . $eventId . '&success=1');
            exit;
        } else {
            // Create new event
            $newEventId = Event::create($data, $_SESSION['user_id']);
            
            // Handle helper types and slots if helpers are needed
            if ($data['needs_helpers']) {
                $helperTypes = json_decode($_POST['helper_types_json'] ?? '[]', true);
                
                foreach ($helperTypes as $helperType) {
                    if (empty($helperType['title'])) continue;
                    
                    $helperTypeId = Event::createHelperType(
                        $newEventId, 
                        $helperType['title'], 
                        $helperType['description'] ?? null,
                        $_SESSION['user_id']
                    );
                    
                    if (!empty($helperType['slots'])) {
                        foreach ($helperType['slots'] as $slot) {
                            if (empty($slot['start_time']) || empty($slot['end_time'])) continue;
                            
                            // Validate slot is within event timeframe
                            $slotStart = strtotime($slot['start_time']);
                            $slotEnd = strtotime($slot['end_time']);
                            $eventStart = strtotime($startTime);
                            $eventEnd = strtotime($endTime);
                            
                            if ($slotStart < $eventStart || $slotEnd > $eventEnd) {
                                throw new Exception('Zeitslots müssen innerhalb des Event-Zeitraums liegen');
                            }
                            
                            if ($slotStart >= $slotEnd) {
                                throw new Exception('Slot-Startzeit muss vor der Endzeit liegen');
                            }
                            
                            Event::createSlot(
                                $helperTypeId,
                                $slot['start_time'],
                                $slot['end_time'],
                                intval($slot['quantity'] ?? 1),
                                $_SESSION['user_id'],
                                $newEventId
                            );
                        }
                    }
                }
            }
            
            $message = 'Event erfolgreich erstellt';
            header('Location: edit.php?id=' . $newEventId . '&success=1');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Check for success message
if (isset($_GET['success'])) {
    $message = $isEdit ? 'Event erfolgreich aktualisiert' : 'Event erfolgreich erstellt';
    // Reload event data
    if ($isEdit) {
        $event = Event::getById($eventId);
        $history = Event::getHistory($eventId, 10);
    }
}

// Release lock when leaving the page (via beforeunload in JS)
if ($isEdit && !$readOnly) {
    // Lock will be released via JavaScript on page unload
}

$title = $isEdit ? 'Event bearbeiten - ' . htmlspecialchars($event['title'] ?? '') : 'Neues Event erstellen';
ob_start();
?>

<div class="mb-6">
    <a href="manage.php" class="text-purple-600 hover:text-purple-700 inline-flex items-center mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Zurück zur Übersicht
    </a>
</div>

<?php if ($lockWarning): ?>
<div class="mb-6 p-4 bg-yellow-100 border border-yellow-400 text-yellow-800 rounded-lg">
    <i class="fas fa-lock mr-2"></i><?php echo $lockWarning; ?>
</div>
<?php endif; ?>

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

<div class="card p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">
        <i class="fas fa-<?php echo $isEdit ? 'edit' : 'plus'; ?> text-purple-600 mr-2"></i>
        <?php echo $isEdit ? 'Event bearbeiten' : 'Neues Event erstellen'; ?>
    </h1>

    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button 
                    class="tab-button active border-purple-500 text-purple-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                    data-tab="basic"
                >
                    <i class="fas fa-info-circle mr-2"></i>
                    Basisdaten
                </button>
                <button 
                    id="helper-tab-button"
                    class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?php echo (!$isEdit || !$event['needs_helpers']) ? 'hidden' : ''; ?>"
                    data-tab="helpers"
                >
                    <i class="fas fa-hands-helping mr-2"></i>
                    Helfer-Konfiguration
                </button>
            </nav>
        </div>
    </div>

    <form method="POST" id="eventForm" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
        <input type="hidden" name="helper_types_json" id="helper_types_json" value="">

        <!-- Tab Content: Basic Data -->
        <div id="tab-basic" class="tab-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Title -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Titel <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="title" 
                        value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>"
                        required 
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                        placeholder="Event-Titel"
                    >
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                    <textarea 
                        name="description" 
                        rows="4"
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                        placeholder="Event-Beschreibung..."
                    ><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ort</label>
                    <input 
                        type="text" 
                        name="location"
                        value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>"
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                        placeholder="Veranstaltungsort"
                    >
                </div>

                <!-- Contact Person -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ansprechpartner</label>
                    <input 
                        type="text" 
                        name="contact_person"
                        value="<?php echo htmlspecialchars($event['contact_person'] ?? ''); ?>"
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                        placeholder="Name des Ansprechpartners"
                    >
                </div>

                <!-- Start Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Startzeit <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="datetime-local" 
                        name="start_time"
                        id="start_time"
                        value="<?php echo $isEdit ? date('Y-m-d\TH:i', strtotime($event['start_time'])) : ''; ?>"
                        required
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                    >
                </div>

                <!-- End Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Endzeit <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="datetime-local" 
                        name="end_time"
                        id="end_time"
                        value="<?php echo $isEdit ? date('Y-m-d\TH:i', strtotime($event['end_time'])) : ''; ?>"
                        required
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                    >
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select 
                        name="status"
                        <?php echo $readOnly ? 'disabled' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                    >
                        <option value="planned" <?php echo ($event['status'] ?? 'planned') === 'planned' ? 'selected' : ''; ?>>Geplant</option>
                        <option value="open" <?php echo ($event['status'] ?? '') === 'open' ? 'selected' : ''; ?>>Offen</option>
                        <option value="running" <?php echo ($event['status'] ?? '') === 'running' ? 'selected' : ''; ?>>Laufend</option>
                        <option value="closed" <?php echo ($event['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Geschlossen</option>
                        <option value="past" <?php echo ($event['status'] ?? '') === 'past' ? 'selected' : ''; ?>>Vergangen</option>
                    </select>
                </div>

                <!-- External Link -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Externer Link</label>
                    <input 
                        type="url" 
                        name="external_link"
                        value="<?php echo htmlspecialchars($event['external_link'] ?? ''); ?>"
                        <?php echo $readOnly ? 'readonly' : ''; ?>
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 <?php echo $readOnly ? 'bg-gray-100' : ''; ?>"
                        placeholder="https://..."
                    >
                </div>

                <!-- Checkboxes -->
                <div class="md:col-span-2">
                    <label class="flex items-center space-x-2 mb-4">
                        <input 
                            type="checkbox" 
                            name="is_external"
                            <?php echo ($event['is_external'] ?? false) ? 'checked' : ''; ?>
                            <?php echo $readOnly ? 'disabled' : ''; ?>
                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                        >
                        <span class="text-sm font-medium text-gray-700">Externes Event</span>
                    </label>

                    <label class="flex items-center space-x-2">
                        <input 
                            type="checkbox" 
                            name="needs_helpers"
                            id="needs_helpers"
                            <?php echo ($event['needs_helpers'] ?? false) ? 'checked' : ''; ?>
                            <?php echo $readOnly ? 'disabled' : ''; ?>
                            class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                        >
                        <span class="text-sm font-medium text-gray-700">Helfer benötigt</span>
                    </label>
                </div>

                <!-- Visibility: Role Checkboxes -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Sichtbarkeit (Rollen)
                        <span class="text-xs text-gray-500 ml-2">Wenn keine Rolle ausgewählt ist, ist das Event für alle sichtbar</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        <?php 
                        $roles = ['member' => 'Mitglied', 'alumni' => 'Alumni', 'manager' => 'Ressortleiter', 'alumni_board' => 'Alumni-Vorstand', 'board' => 'Vorstand', 'admin' => 'Administrator'];
                        $allowedRoles = $event['allowed_roles'] ?? [];
                        foreach ($roles as $roleValue => $roleLabel): 
                        ?>
                        <label class="flex items-center space-x-2">
                            <input 
                                type="checkbox" 
                                name="allowed_roles[]"
                                value="<?php echo $roleValue; ?>"
                                <?php echo in_array($roleValue, $allowedRoles) ? 'checked' : ''; ?>
                                <?php echo $readOnly ? 'disabled' : ''; ?>
                                class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
                            >
                            <span class="text-sm text-gray-700"><?php echo $roleLabel; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Helper Configuration -->
        <div id="tab-helpers" class="tab-content hidden">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Helfer-Konfiguration</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Definieren Sie die verschiedenen Helfer-Arten und deren Zeitslots für dieses Event.
                </p>
            </div>

            <div id="helper-types-container" class="space-y-6">
                <!-- Helper types will be added here dynamically -->
            </div>

            <?php if (!$readOnly): ?>
            <button 
                type="button" 
                onclick="addHelperType()" 
                class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
            >
                <i class="fas fa-plus mr-2"></i>Helfer-Art hinzufügen
            </button>
            <?php endif; ?>
        </div>

        <!-- Form Actions -->
        <?php if (!$readOnly): ?>
        <div class="flex space-x-4 pt-6 border-t">
            <a href="manage.php" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-center">
                Abbrechen
            </a>
            <button type="submit" class="flex-1 btn-primary">
                <i class="fas fa-save mr-2"></i><?php echo $isEdit ? 'Speichern' : 'Erstellen'; ?>
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- History Section -->
<?php if ($isEdit && !empty($history)): ?>
<div class="card p-6 mt-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-history text-purple-600 mr-2"></i>
        Änderungshistorie (letzte 10 Einträge)
    </h2>
    <div class="space-y-3">
        <?php foreach ($history as $entry): 
            $user = User::getById($entry['user_id']);
            $details = json_decode($entry['change_details'], true);
        ?>
        <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
            <div class="flex-shrink-0">
                <i class="fas fa-circle text-purple-600 text-xs mt-1"></i>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-medium text-gray-800">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </span>
                    <span class="text-xs text-gray-500">
                        <?php echo date('d.m.Y H:i', strtotime($entry['timestamp'])); ?>
                    </span>
                </div>
                <div class="text-sm text-gray-600">
                    <span class="font-semibold"><?php echo htmlspecialchars($entry['change_type']); ?>:</span>
                    <?php echo htmlspecialchars($details['action'] ?? 'Änderung durchgeführt'); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Tab switching
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        
        // Update buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-purple-500', 'text-purple-600');
            btn.classList.add('border-transparent', 'text-gray-500');
        });
        this.classList.add('active', 'border-purple-500', 'text-purple-600');
        this.classList.remove('border-transparent', 'text-gray-500');
        
        // Update content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        document.getElementById('tab-' + targetTab).classList.remove('hidden');
    });
});

// Show/hide helper tab based on checkbox
const needsHelpersCheckbox = document.getElementById('needs_helpers');
const helperTabButton = document.getElementById('helper-tab-button');

needsHelpersCheckbox?.addEventListener('change', function() {
    if (this.checked) {
        helperTabButton.classList.remove('hidden');
    } else {
        helperTabButton.classList.add('hidden');
        // Switch to basic tab if currently on helpers tab
        if (!document.getElementById('tab-helpers').classList.contains('hidden')) {
            document.querySelector('[data-tab="basic"]').click();
        }
    }
});

// Dynamic helper type management
let helperTypeCounter = 0;

function addHelperType() {
    const container = document.getElementById('helper-types-container');
    const typeId = 'helper-type-' + (++helperTypeCounter);
    
    const helperTypeHtml = `
        <div id="${typeId}" class="p-4 border-2 border-gray-200 rounded-lg bg-white">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-bold text-gray-800">Helfer-Art #${helperTypeCounter}</h4>
                <button type="button" onclick="removeHelperType('${typeId}')" class="text-red-600 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Titel <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        class="helper-type-title w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="z.B. Aufbau, Abbau, Catering"
                        required
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                    <input 
                        type="text" 
                        class="helper-type-description w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Optionale Beschreibung"
                    >
                </div>
            </div>
            
            <div class="border-t pt-4">
                <h5 class="text-sm font-bold text-gray-700 mb-3">Zeitslots</h5>
                <div class="slots-container space-y-3" data-type-id="${typeId}">
                    <!-- Slots will be added here -->
                </div>
                <button 
                    type="button" 
                    onclick="addSlot('${typeId}')" 
                    class="mt-3 px-3 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition"
                >
                    <i class="fas fa-plus mr-1"></i>Zeitslot hinzufügen
                </button>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', helperTypeHtml);
}

function removeHelperType(typeId) {
    if (confirm('Möchten Sie diese Helfer-Art wirklich entfernen?')) {
        document.getElementById(typeId).remove();
    }
}

function addSlot(typeId) {
    const slotsContainer = document.querySelector(`[data-type-id="${typeId}"]`);
    const slotId = 'slot-' + Date.now();
    
    const slotHtml = `
        <div id="${slotId}" class="grid grid-cols-1 md:grid-cols-4 gap-3 p-3 bg-gray-50 rounded-lg">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Start <span class="text-red-500">*</span>
                </label>
                <input 
                    type="datetime-local" 
                    class="slot-start w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    required
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Ende <span class="text-red-500">*</span>
                </label>
                <input 
                    type="datetime-local" 
                    class="slot-end w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    required
                >
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Anzahl <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number" 
                    class="slot-quantity w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    min="1"
                    value="1"
                    required
                >
            </div>
            <div class="flex items-end">
                <button 
                    type="button" 
                    onclick="removeSlot('${slotId}')" 
                    class="w-full px-3 py-2 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition"
                >
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    slotsContainer.insertAdjacentHTML('beforeend', slotHtml);
}

function removeSlot(slotId) {
    document.getElementById(slotId).remove();
}

// Collect helper types data before form submission
document.getElementById('eventForm')?.addEventListener('submit', function(e) {
    const helperTypes = [];
    
    document.querySelectorAll('#helper-types-container > div').forEach(typeDiv => {
        const title = typeDiv.querySelector('.helper-type-title')?.value;
        const description = typeDiv.querySelector('.helper-type-description')?.value;
        
        if (!title) return;
        
        const slots = [];
        typeDiv.querySelectorAll('.slots-container > div').forEach(slotDiv => {
            const startTime = slotDiv.querySelector('.slot-start')?.value;
            const endTime = slotDiv.querySelector('.slot-end')?.value;
            const quantity = slotDiv.querySelector('.slot-quantity')?.value;
            
            if (startTime && endTime) {
                slots.push({
                    start_time: startTime,
                    end_time: endTime,
                    quantity: parseInt(quantity) || 1
                });
            }
        });
        
        helperTypes.push({
            title: title,
            description: description,
            slots: slots
        });
    });
    
    document.getElementById('helper_types_json').value = JSON.stringify(helperTypes);
});

// Load existing helper types if editing
<?php if ($isEdit && $event['needs_helpers'] && !empty($event['helper_types'])): ?>
window.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($event['helper_types'] as $helperType): ?>
    addHelperType();
    const lastType = document.querySelector('#helper-types-container > div:last-child');
    lastType.querySelector('.helper-type-title').value = <?php echo json_encode($helperType['title']); ?>;
    lastType.querySelector('.helper-type-description').value = <?php echo json_encode($helperType['description'] ?? ''); ?>;
    
    <?php if (!empty($helperType['slots'])): ?>
    <?php foreach ($helperType['slots'] as $slot): ?>
    addSlot(lastType.id);
    const lastSlot = lastType.querySelector('.slots-container > div:last-child');
    lastSlot.querySelector('.slot-start').value = '<?php echo date('Y-m-d\TH:i', strtotime($slot['start_time'])); ?>';
    lastSlot.querySelector('.slot-end').value = '<?php echo date('Y-m-d\TH:i', strtotime($slot['end_time'])); ?>';
    lastSlot.querySelector('.slot-quantity').value = <?php echo $slot['quantity_needed']; ?>;
    <?php endforeach; ?>
    <?php endif; ?>
    <?php endforeach; ?>
});
<?php endif; ?>

// Release lock on page unload (only if we have the lock)
<?php if ($isEdit && !$readOnly): ?>
window.addEventListener('beforeunload', function() {
    // Use sendBeacon for reliable request on page unload
    const formData = new FormData();
    formData.append('event_id', <?php echo $eventId; ?>);
    formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
    navigator.sendBeacon('<?php echo dirname($_SERVER['PHP_SELF']); ?>/release_lock.php', formData);
});
<?php endif; ?>

// Client-side validation for times
document.getElementById('eventForm')?.addEventListener('submit', function(e) {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startTime && endTime && new Date(startTime) >= new Date(endTime)) {
        e.preventDefault();
        alert('Die Startzeit muss vor der Endzeit liegen!');
        return false;
    }
    
    // Validate slots if helpers are needed
    if (document.getElementById('needs_helpers')?.checked) {
        const eventStart = new Date(startTime);
        const eventEnd = new Date(endTime);
        let isValid = true;
        
        document.querySelectorAll('.slots-container > div').forEach(slotDiv => {
            const slotStart = new Date(slotDiv.querySelector('.slot-start')?.value);
            const slotEnd = new Date(slotDiv.querySelector('.slot-end')?.value);
            
            if (slotStart && slotEnd) {
                if (slotStart < eventStart || slotEnd > eventEnd) {
                    e.preventDefault();
                    alert('Alle Zeitslots müssen innerhalb des Event-Zeitraums liegen!');
                    isValid = false;
                    return false;
                }
                if (slotStart >= slotEnd) {
                    e.preventDefault();
                    alert('Slot-Startzeit muss vor der Endzeit liegen!');
                    isValid = false;
                    return false;
                }
            }
        });
        
        if (!isValid) return false;
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
