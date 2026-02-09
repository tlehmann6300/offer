<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';
require_once __DIR__ . '/../../includes/models/Event.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Update event statuses (pseudo-cron)
require_once __DIR__ . '/../../includes/pseudo_cron.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = Auth::user()['role'] ?? '';
$stats = Inventory::getDashboardStats();

// Get user's first name for personalized greeting
$firstname = 'Benutzer'; // Default fallback
if (!empty($user['firstname'])) {
    $firstname = $user['firstname'];
} elseif (!empty($user['email']) && strpos($user['email'], '@') !== false) {
    $emailParts = explode('@', $user['email']);
    $firstname = $emailParts[0];
}

// Determine greeting based on time of day (German time)
$timezone = new DateTimeZone('Europe/Berlin');
$now = new DateTime('now', $timezone);
$hour = (int)$now->format('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Guten Morgen';
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = 'Guten Tag';
} else {
    $greeting = 'Guten Abend';
}

// Get user's upcoming events
$userUpcomingEvents = Event::getUserSignups($user['id']);
$nextEvent = null;
if (!empty($userUpcomingEvents)) {
    // Filter for upcoming events only and get the next one
    $upcomingEvents = array_filter($userUpcomingEvents, function($signup) {
        return !empty($signup['start_time']) && strtotime($signup['start_time']) > time();
    });
    if (!empty($upcomingEvents)) {
        // Sort by start_time
        usort($upcomingEvents, function($a, $b) {
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });
        $nextEvent = $upcomingEvents[0];
    }
}

// Get user's open tasks from inventory rentals
$userRentals = Inventory::getUserCheckouts($user['id'], false); // false = only unreturned items
$openTasksCount = count($userRentals);

// Get events that need helpers (for all users)
$contentDb = Database::getContentDB();
$stmt = $contentDb->query("
    SELECT e.id, e.title, e.description, e.start_time, e.end_time, e.location
    FROM events e
    WHERE e.helper_slots > 0
    AND e.status IN ('open', 'planned')
    AND e.end_time >= NOW()
    ORDER BY e.start_time ASC
    LIMIT 5
");
$helperEvents = $stmt->fetchAll();

// Security Audit - nur für Board/Head
$securityWarning = '';
if (in_array($userRole, ['board', 'head'])) {
    require_once __DIR__ . '/../../security_audit.php';
    $securityWarning = SecurityAudit::getDashboardWarning(__DIR__ . '/../..');
}

$title = 'Dashboard - IBC Intranet';
ob_start();
?>

<?php if (!empty($user['prompt_profile_review']) && $user['prompt_profile_review'] == 1): ?>
<!-- Profile Review Prompt Modal -->
<div id="profile-review-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-4">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-user-edit text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Deine Rolle wurde geändert!</h3>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="px-6 py-6">
            <p class="text-gray-700 dark:text-gray-300 text-lg mb-6">
                Bitte überprüfe deine Daten (besonders E-Mail und Job-Daten), damit wir in Kontakt bleiben können.
            </p>
            
            <div class="bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-700 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-purple-600 dark:text-purple-400 mt-1 mr-3"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Es ist wichtig, dass deine Kontaktdaten aktuell sind, damit du alle wichtigen Informationen erhältst.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex flex-col sm:flex-row gap-3">
            <a href="../auth/profile.php" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-user-circle mr-2"></i>
                Zum Profil
            </a>
            <button onclick="dismissProfileReviewPrompt()" class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-semibold hover:bg-gray-400 dark:hover:bg-gray-500 transition-all duration-300">
                Später
            </button>
        </div>
    </div>
</div>

<script>
// Dismiss profile review prompt and update database
function dismissProfileReviewPrompt() {
    // Construct API path relative to web root
    const baseUrl = window.location.origin;
    const apiPath = baseUrl + '/api/dismiss_profile_review.php';
    
    // Make AJAX call to update database
    fetch(apiPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide modal
            document.getElementById('profile-review-modal').style.display = 'none';
        } else {
            console.error('Failed to dismiss prompt:', data.message);
            // Hide modal anyway to prevent blocking user
            document.getElementById('profile-review-modal').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Hide modal anyway to prevent blocking user
        document.getElementById('profile-review-modal').style.display = 'none';
    });
}
</script>
<?php endif; ?>

<?php if (isset($_SESSION['show_2fa_nudge']) && $_SESSION['show_2fa_nudge']): ?>
<!-- 2FA Nudge Modal -->
<div id="tfa-nudge-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-green-600 px-6 py-4">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white">Sicherheitshinweis</h3>
            </div>
        </div>
        
        <!-- Modal Body -->
        <div class="px-6 py-6">
            <p class="text-gray-700 dark:text-gray-300 text-lg mb-2 font-semibold">
                Erhöhe deine Sicherheit!
            </p>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Aktiviere jetzt die 2-Faktor-Authentifizierung für zusätzlichen Schutz deines Kontos.
            </p>
            
            <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mt-1 mr-3"></i>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Die 2-Faktor-Authentifizierung macht dein Konto deutlich sicherer, indem bei der Anmeldung ein zusätzlicher Code erforderlich ist.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex flex-col sm:flex-row gap-3">
            <a href="../auth/profile.php" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-green-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-green-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-shield-alt mr-2"></i>
                Jetzt einrichten
            </a>
            <button onclick="dismissTfaNudge()" class="flex-1 px-6 py-3 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg font-semibold hover:bg-gray-400 dark:hover:bg-gray-500 transition-all duration-300">
                Später
            </button>
        </div>
    </div>
</div>

<script>
// Dismiss modal
function dismissTfaNudge() {
    document.getElementById('tfa-nudge-modal').style.display = 'none';
}
</script>
<?php 
    unset($_SESSION['show_2fa_nudge']);
endif; 
?>

<?php if (!empty($securityWarning)): ?>
<?php echo $securityWarning; ?>
<?php endif; ?>

<!-- Hero Section with Personalized Greeting -->
<div class="mb-8">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-800 dark:text-gray-100 mb-3">
            <?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($firstname); ?>!
        </h1>
        <p class="text-lg md:text-xl text-gray-600 dark:text-gray-300">
            Willkommen zurück im IBC Intranet
        </p>
    </div>
</div>

<!-- Quick Stats Widgets -->
<div class="max-w-6xl mx-auto mb-8">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4">
        <i class="fas fa-tachometer-alt text-purple-600 mr-2"></i>
        Schnellübersicht
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- My Open Tasks Widget -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-orange-50 dark:from-gray-800 dark:to-gray-700 hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                    <i class="fas fa-tasks text-orange-600 mr-2"></i>
                    Meine offenen Ausleihen
                </h3>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-orange-600 dark:text-orange-300"><?php echo $openTasksCount; ?></span>
                </div>
            </div>
            <?php if ($openTasksCount > 0): ?>
            <p class="text-gray-600 dark:text-gray-300 mb-3">Du hast aktuell <?php echo $openTasksCount; ?> offene Ausleihen</p>
            <a href="/pages/inventory/my_checkouts.php" class="inline-flex items-center text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-semibold">
                Ausleihen verwalten <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <?php else: ?>
            <p class="text-gray-600 dark:text-gray-300">Keine offenen Ausleihen</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Alle Artikel wurden zurückgegeben</p>
            <?php endif; ?>
        </div>

        <!-- Next Event Widget -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-blue-50 dark:from-gray-800 dark:to-gray-700 hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                    <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                    Nächstes Event
                </h3>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-blue-600 dark:text-blue-300 text-xl"></i>
                </div>
            </div>
            <?php if ($nextEvent): ?>
            <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2"><?php echo htmlspecialchars($nextEvent['title']); ?></h4>
            <p class="text-gray-600 dark:text-gray-300 mb-3">
                <i class="fas fa-clock mr-1"></i>
                <?php echo date('d.m.Y H:i', strtotime($nextEvent['start_time'])); ?> Uhr
            </p>
            <a href="../events/view.php?id=<?php echo $nextEvent['event_id']; ?>" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold">
                Details ansehen <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <?php else: ?>
            <p class="text-gray-600 dark:text-gray-300 mb-3">Keine anstehenden Events</p>
            <a href="../events/index.php" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold">
                Events durchsuchen <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Dashboard Section - Wir suchen Helfer -->
<div class="max-w-6xl mx-auto mb-12">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
        <i class="fas fa-hands-helping text-green-600 mr-2"></i>
        Wir suchen Helfer
    </h2>
    
    <?php if (!empty($helperEvents)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($helperEvents as $event): ?>
        <div class="card p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 bg-gradient-to-br from-white to-green-50 dark:from-gray-800 dark:to-gray-700 border-l-4 border-green-500">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 mb-2">
                    <i class="fas fa-calendar-alt text-green-600 mr-2"></i>
                    <?php echo htmlspecialchars($event['title']); ?>
                </h3>
                <?php if (!empty($event['description'])): ?>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                    <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                <div class="flex items-center mb-1">
                    <i class="fas fa-clock mr-2 text-green-600"></i>
                    <?php echo date('d.m.Y H:i', strtotime($event['start_time'])); ?> Uhr
                </div>
                <?php if (!empty($event['location'])): ?>
                <div class="flex items-center">
                    <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>
                    <?php echo htmlspecialchars($event['location']); ?>
                </div>
                <?php endif; ?>
            </div>
            <a href="../events/view.php?id=<?php echo $event['id']; ?>" 
               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all font-semibold">
                Mehr erfahren <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="card p-8 rounded-xl shadow-lg text-center bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700">
        <i class="fas fa-hands-helping text-4xl mb-3 text-gray-400 dark:text-gray-500"></i>
        <p class="text-gray-600 dark:text-gray-300 text-lg">Aktuell werden keine Helfer gesucht</p>
        <a href="../events/index.php" class="inline-flex items-center mt-4 text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 font-semibold">
            Alle Events ansehen <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Upcoming Events Section - Visible to All Users -->
<div class="max-w-6xl mx-auto mb-12">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 text-center">
        <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
        Anstehende Events
    </h2>
    
    <div class="grid grid-cols-1 gap-6">
        <?php 
        // Get upcoming events (upcoming status, ordered by start time)
        $upcomingEventsForAllUsers = Event::getEvents([
            'status' => ['upcoming', 'registration_open'],
            'start_date' => date('Y-m-d H:i:s')
        ], $user['role']);
        
        // Limit to 5 events
        $upcomingEventsForAllUsers = array_slice($upcomingEventsForAllUsers, 0, 5);
        
        if (!empty($upcomingEventsForAllUsers)): 
        ?>
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-blue-50 dark:from-gray-800 dark:to-gray-700">
            <div class="space-y-4">
                <?php foreach ($upcomingEventsForAllUsers as $event): ?>
                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all">
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-1"><?php echo htmlspecialchars($event['title']); ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <i class="fas fa-clock mr-1"></i>
                            <?php echo date('d.m.Y H:i', strtotime($event['start_time'])); ?> Uhr
                        </p>
                        <?php if (!empty($event['location'])): ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo htmlspecialchars($event['location']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <a href="../events/view.php?id=<?php echo $event['id']; ?>" class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                        Details
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="card p-8 rounded-xl shadow-lg text-center bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-700">
            <i class="fas fa-calendar-times text-4xl mb-3 text-gray-400 dark:text-gray-500"></i>
            <p class="text-gray-600 dark:text-gray-300 text-lg">Keine anstehenden Events</p>
            <a href="../events/index.php" class="inline-flex items-center mt-4 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold">
                Alle Events ansehen <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
