<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/Event.php';

AuthHandler::startSession();

// Check authentication
if (!AuthHandler::isAuthenticated()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = AuthHandler::getCurrentUser();
$userRole = $_SESSION['user_role'] ?? 'member';

// Get event ID
$eventId = $_GET['id'] ?? null;
if (!$eventId) {
    header('Location: index.php');
    exit;
}

// Get event details
$event = Event::getById($eventId, true);
if (!$event) {
    header('Location: index.php');
    exit;
}

// Check if user has permission to view this event
$allowedRoles = $event['allowed_roles'] ?? [];
if (!empty($allowedRoles) && !in_array($userRole, $allowedRoles)) {
    header('Location: index.php');
    exit;
}

// Get user's signups
$userSignups = Event::getUserSignups($user['id']);
$isRegistered = false;
$userSignupId = null;
$userSlotId = null;
foreach ($userSignups as $signup) {
    if ($signup['event_id'] == $eventId) {
        $isRegistered = true;
        $userSignupId = $signup['id'];
        $userSlotId = $signup['slot_id'];
        break;
    }
}

// Get helper types and slots if needed
$helperTypes = [];
if ($event['needs_helpers'] && $userRole !== 'alumni') {
    $helperTypes = Event::getHelperTypes($eventId);
    
    // For each helper type, get slots with signup counts
    foreach ($helperTypes as &$helperType) {
        $slots = Event::getSlots($helperType['id']);
        
        // Add signup counts to each slot
        foreach ($slots as &$slot) {
            $signups = Event::getSignups($eventId);
            $confirmedCount = 0;
            $userInSlot = false;
            
            foreach ($signups as $signup) {
                if ($signup['slot_id'] == $slot['id'] && $signup['status'] == 'confirmed') {
                    $confirmedCount++;
                    if ($signup['user_id'] == $user['id']) {
                        $userInSlot = true;
                    }
                }
            }
            
            $slot['signups_count'] = $confirmedCount;
            $slot['user_in_slot'] = $userInSlot;
            $slot['is_full'] = $confirmedCount >= $slot['quantity_needed'];
        }
        
        $helperType['slots'] = $slots;
    }
}

// Check if event signup has a deadline
$signupDeadline = $event['start_time']; // Default to event start time
$canCancel = strtotime($signupDeadline) > time();

$title = htmlspecialchars($event['title']) . ' - Events';
ob_start();
?>

<div class="max-w-5xl mx-auto">
    <!-- Back Button -->
    <a href="index.php" class="inline-flex items-center text-purple-600 hover:text-purple-700 mb-6">
        <i class="fas fa-arrow-left mr-2"></i>
        Zurück zur Übersicht
    </a>

    <!-- Event Header -->
    <div class="card p-8 mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start gap-4 mb-6">
            <div class="flex-1">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
                    <?php echo htmlspecialchars($event['title']); ?>
                </h1>
                
                <!-- Status Badge -->
                <?php 
                $statusLabels = [
                    'planned' => ['label' => 'Geplant', 'color' => 'bg-gray-100 text-gray-800'],
                    'open' => ['label' => 'Anmeldung offen', 'color' => 'bg-green-100 text-green-800'],
                    'closed' => ['label' => 'Anmeldung geschlossen', 'color' => 'bg-yellow-100 text-yellow-800'],
                    'running' => ['label' => 'Läuft gerade', 'color' => 'bg-blue-100 text-blue-800'],
                    'past' => ['label' => 'Beendet', 'color' => 'bg-gray-100 text-gray-600']
                ];
                $currentStatus = $event['status'] ?? 'planned';
                $statusInfo = $statusLabels[$currentStatus] ?? ['label' => $currentStatus, 'color' => 'bg-gray-100 text-gray-800'];
                ?>
                <div class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-sm <?php echo $statusInfo['color']; ?> mb-4">
                    <i class="fas fa-circle text-xs mr-2"></i>
                    <?php echo $statusInfo['label']; ?>
                </div>
                
                <!-- Event Meta -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-600">
                    <div class="flex items-start">
                        <i class="fas fa-calendar w-6 mt-1 text-purple-600"></i>
                        <div>
                            <div class="font-semibold">Beginn</div>
                            <div><?php echo date('d.m.Y H:i', strtotime($event['start_time'])); ?> Uhr</div>
                        </div>
                    </div>
                    
                    <div class="flex items-start">
                        <i class="fas fa-clock w-6 mt-1 text-purple-600"></i>
                        <div>
                            <div class="font-semibold">Ende</div>
                            <div><?php echo date('d.m.Y H:i', strtotime($event['end_time'])); ?> Uhr</div>
                        </div>
                    </div>
                    
                    <?php if (!empty($event['location'])): ?>
                        <div class="flex items-start">
                            <i class="fas fa-map-marker-alt w-6 mt-1 text-purple-600"></i>
                            <div class="flex-1">
                                <div class="font-semibold">Veranstaltungsort</div>
                                <div class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($event['location']); ?></div>
                                <?php if (!empty($event['maps_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($event['maps_link']); ?>" 
                                       target="_blank" 
                                       rel="noopener noreferrer"
                                       class="inline-flex items-center mt-2 text-sm text-purple-600 hover:text-purple-700 font-semibold">
                                        <i class="fas fa-map-marked-alt mr-1"></i>
                                        Auf Karte anzeigen
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['contact_person'])): ?>
                        <div class="flex items-start">
                            <i class="fas fa-user w-6 mt-1 text-purple-600"></i>
                            <div>
                                <div class="font-semibold">Ansprechpartner</div>
                                <div><?php echo htmlspecialchars($event['contact_person']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Registration Status -->
            <?php if ($isRegistered): ?>
                <div class="flex-shrink-0">
                    <div class="px-6 py-3 bg-green-100 text-green-800 rounded-lg font-semibold text-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        Angemeldet
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <?php if (!empty($event['description'])): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-800 mb-3">Beschreibung</h2>
                <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($event['description']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Participation Button -->
        <div class="flex gap-4">
            <?php if ($event['is_external']): ?>
                <!-- External Event - Open Link -->
                <?php if (!empty($event['external_link'])): ?>
                    <a href="<?php echo htmlspecialchars($event['external_link']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Zur Anmeldung (extern)
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <!-- Internal Event - AJAX Signup -->
                <?php if (!$isRegistered && !$userSlotId): ?>
                    <button onclick="signupForEvent(<?php echo $eventId; ?>)" 
                            class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-purple-800 transition-all shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i>
                        Teilnehmen
                    </button>
                <?php elseif ($canCancel && $userSignupId && !$userSlotId): ?>
                    <button onclick="cancelSignup(<?php echo $userSignupId; ?>)" 
                            class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg font-semibold hover:from-red-700 hover:to-red-800 transition-all shadow-lg">
                        <i class="fas fa-user-times mr-2"></i>
                        Abmelden
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Helper Slots Section (Only for non-alumni and if event needs helpers) -->
    <?php if ($event['needs_helpers'] && $userRole !== 'alumni' && !empty($helperTypes)): ?>
        <div class="card p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-hands-helping mr-2 text-orange-600"></i>
                Helfer-Bereich
            </h2>
            
            <p class="text-gray-600 mb-6">Unterstützen Sie uns als Helfer! Wählen Sie einen freien Slot aus.</p>
            
            <?php foreach ($helperTypes as $helperType): ?>
                <div class="mb-6 last:mb-0">
                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                        <?php echo htmlspecialchars($helperType['title']); ?>
                    </h3>
                    
                    <?php if (!empty($helperType['description'])): ?>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($helperType['description']); ?></p>
                    <?php endif; ?>
                    
                    <!-- Slots -->
                    <div class="space-y-3">
                        <?php foreach ($helperType['slots'] as $slot): ?>
                            <?php
                                $slotStart = new DateTime($slot['start_time']);
                                $slotEnd = new DateTime($slot['end_time']);
                                $occupancy = $slot['signups_count'] . '/' . $slot['quantity_needed'];
                                $canSignup = !$slot['is_full'] && !$slot['user_in_slot'];
                                $onWaitlist = $slot['is_full'] && !$slot['user_in_slot'];
                                
                                // Prepare slot parameters for onclick handlers
                                $slotStartFormatted = htmlspecialchars($slotStart->format('Y-m-d H:i:s'), ENT_QUOTES);
                                $slotEndFormatted = htmlspecialchars($slotEnd->format('Y-m-d H:i:s'), ENT_QUOTES);
                                $slotSignupHandler = "signupForSlot({$eventId}, {$slot['id']}, '{$slotStartFormatted}', '{$slotEndFormatted}')";
                            ?>
                            
                            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex-1">
                                    <div class="font-semibold text-gray-800">
                                        <i class="fas fa-clock mr-2 text-purple-600"></i>
                                        <?php echo $slotStart->format('H:i'); ?> - <?php echo $slotEnd->format('H:i'); ?> Uhr
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span class="font-semibold"><?php echo $occupancy; ?> belegt</span>
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <?php if ($slot['user_in_slot']): ?>
                                        <div class="flex items-center gap-3">
                                            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-lg font-semibold text-sm">
                                                <i class="fas fa-check mr-1"></i>
                                                Eingetragen
                                            </span>
                                            <?php if ($canCancel): ?>
                                                <button onclick="cancelHelperSlot(<?php echo $userSignupId; ?>)" 
                                                        class="px-4 py-2 bg-red-100 text-red-800 rounded-lg font-semibold text-sm hover:bg-red-200 transition-all">
                                                    <i class="fas fa-times mr-1"></i>
                                                    Austragen
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($canSignup): ?>
                                        <button onclick="<?php echo $slotSignupHandler; ?>" 
                                                class="px-6 py-2 bg-gradient-to-r from-orange-600 to-orange-700 text-white rounded-lg font-semibold hover:from-orange-700 hover:to-orange-800 transition-all">
                                            <i class="fas fa-user-plus mr-2"></i>
                                            Eintragen
                                        </button>
                                    <?php elseif ($onWaitlist): ?>
                                        <button onclick="<?php echo $slotSignupHandler; ?>" 
                                                class="px-6 py-2 bg-gradient-to-r from-yellow-600 to-yellow-700 text-white rounded-lg font-semibold hover:from-yellow-700 hover:to-yellow-800 transition-all">
                                            <i class="fas fa-list mr-2"></i>
                                            Warteliste
                                        </button>
                                    <?php else: ?>
                                        <span class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg font-semibold text-sm">
                                            Belegt
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Success/Error Message Container -->
<div id="message-container" class="fixed top-4 right-4 z-50 hidden">
    <div id="message-content" class="card px-6 py-4 shadow-2xl"></div>
</div>

<script>
// Show message helper
function showMessage(message, type = 'success') {
    const container = document.getElementById('message-container');
    const content = document.getElementById('message-content');
    
    const bgColor = type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    content.className = `card px-6 py-4 shadow-2xl ${bgColor}`;
    content.innerHTML = `<i class="fas ${icon} mr-2"></i>${message}`;
    
    container.classList.remove('hidden');
    
    setTimeout(() => {
        container.classList.add('hidden');
    }, 5000);
}

// Signup for event (general participation)
function signupForEvent(eventId) {
    fetch('/api/event_signup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'signup',
            event_id: eventId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Erfolgreich angemeldet!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showMessage(data.message || 'Fehler bei der Anmeldung', 'error');
        }
    })
    .catch(error => {
        showMessage('Netzwerkfehler', 'error');
    });
}

// Signup for helper slot
function signupForSlot(eventId, slotId, slotStart, slotEnd) {
    fetch('/api/event_signup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'signup',
            event_id: eventId,
            slot_id: slotId,
            slot_start: slotStart,
            slot_end: slotEnd
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.status === 'waitlist') {
                showMessage('Sie wurden auf die Warteliste gesetzt', 'success');
            } else {
                showMessage('Erfolgreich eingetragen!', 'success');
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            showMessage(data.message || 'Fehler bei der Anmeldung', 'error');
        }
    })
    .catch(error => {
        showMessage('Netzwerkfehler', 'error');
    });
}

// Cancel signup (general or helper slot)
function cancelSignup(signupId, message = 'Möchten Sie Ihre Anmeldung wirklich stornieren?', successMessage = 'Abmeldung erfolgreich') {
    if (!confirm(message)) {
        return;
    }
    
    fetch('/api/event_signup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'cancel',
            signup_id: signupId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(successMessage, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showMessage(data.message || 'Fehler bei der Abmeldung', 'error');
        }
    })
    .catch(error => {
        showMessage('Netzwerkfehler', 'error');
    });
}

// Cancel helper slot (wrapper for consistency)
function cancelHelperSlot(signupId) {
    cancelSignup(signupId, 'Möchten Sie sich wirklich austragen?', 'Erfolgreich ausgetragen');
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
