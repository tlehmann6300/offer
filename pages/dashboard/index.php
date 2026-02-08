<?php
require_once __DIR__ . '/../../src/Auth.php';
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
// TODO: Move timezone configuration to config file in future refactoring
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

// Get extended statistics for board/managers
$hasExtendedAccess = Auth::hasPermission('manager');
if ($hasExtendedAccess) {
    $inStockStats = Inventory::getInStockStats();
    $checkedOutStats = Inventory::getCheckedOutStats();
    $writeOffStats = Inventory::getWriteOffStatsThisMonth();
}

// Security Audit - nur für Admins
$securityWarning = '';
if (Auth::hasPermission('admin')) {
    require_once __DIR__ . '/../../security_audit.php';
    $securityWarning = SecurityAudit::getDashboardWarning(__DIR__ . '/../..');
}

$title = 'Dashboard - IBC Intranet';
ob_start();
?>

<?php if (!empty($user['prompt_profile_review']) && $user['prompt_profile_review'] == 1): ?>
<!-- Profile Review Prompt Modal -->
<div id="profile-review-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all">
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
            <p class="text-gray-700 text-lg mb-6">
                Bitte überprüfe deine Daten (besonders E-Mail und Job-Daten), damit wir in Kontakt bleiben können.
            </p>
            
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-purple-600 mt-1 mr-3"></i>
                    <p class="text-sm text-gray-700">
                        Es ist wichtig, dass deine Kontaktdaten aktuell sind, damit du alle wichtigen Informationen erhältst.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row gap-3">
            <a href="../auth/profile.php" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-user-circle mr-2"></i>
                Zum Profil
            </a>
            <button onclick="dismissProfileReviewPrompt()" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-400 transition-all duration-300">
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
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden transform transition-all">
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
            <p class="text-gray-700 text-lg mb-2 font-semibold">
                Erhöhen Sie Ihre Sicherheit!
            </p>
            <p class="text-gray-600 mb-6">
                Aktivieren Sie jetzt die 2-Faktor-Authentifizierung für zusätzlichen Schutz Ihres Kontos.
            </p>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <p class="text-sm text-gray-700">
                        Die 2-Faktor-Authentifizierung macht Ihr Konto deutlich sicherer, indem bei der Anmeldung ein zusätzlicher Code erforderlich ist.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-4 bg-gray-50 flex flex-col sm:flex-row gap-3">
            <a href="../auth/profile.php" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-green-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-green-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-shield-alt mr-2"></i>
                Jetzt einrichten
            </a>
            <button onclick="dismissTfaNudge()" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-400 transition-all duration-300">
                Später
            </button>
        </div>
    </div>
</div>

<script>
// Dismiss modal - Note: Session variable is already unset on page load (line 89)
// so the modal won't reappear in this session even if user navigates back to dashboard
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
        <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-3">
            <?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($firstname); ?>!
        </h1>
        <p class="text-lg md:text-xl text-gray-600">
            Willkommen zurück im IBC Intranet
        </p>
    </div>
</div>

<!-- Quick Stats Widgets -->
<div class="max-w-6xl mx-auto mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
        <i class="fas fa-tachometer-alt text-purple-600 mr-2"></i>
        Schnellübersicht
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- My Open Tasks Widget -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-orange-50 hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-tasks text-orange-600 mr-2"></i>
                    Meine offenen Ausleihen
                </h3>
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-orange-600"><?php echo $openTasksCount; ?></span>
                </div>
            </div>
            <?php if ($openTasksCount > 0): ?>
            <p class="text-gray-600 mb-3">Sie haben aktuell <?php echo $openTasksCount; ?> offene Ausleihen</p>
            <a href="../inventory/my_rentals.php" class="inline-flex items-center text-orange-600 hover:text-orange-700 font-semibold">
                Ausleihen verwalten <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <?php else: ?>
            <p class="text-gray-600">Keine offenen Ausleihen</p>
            <p class="text-sm text-gray-500 mt-2">Alle Artikel wurden zurückgegeben</p>
            <?php endif; ?>
        </div>

        <!-- Next Event Widget -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-blue-50 hover:shadow-2xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                    Nächstes Event
                </h3>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-check text-blue-600 text-xl"></i>
                </div>
            </div>
            <?php if ($nextEvent): ?>
            <h4 class="font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($nextEvent['title']); ?></h4>
            <p class="text-gray-600 mb-3">
                <i class="fas fa-clock mr-1"></i>
                <?php echo date('d.m.Y H:i', strtotime($nextEvent['start_time'])); ?> Uhr
            </p>
            <a href="../events/view.php?id=<?php echo $nextEvent['event_id']; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold">
                Details ansehen <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <?php else: ?>
            <p class="text-gray-600 mb-3">Keine anstehenden Events</p>
            <a href="../events/index.php" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold">
                Events durchsuchen <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Schnellzugriff Section -->
<div class="max-w-6xl mx-auto mb-12">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
        Schnellzugriff
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="../auth/profile.php" class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white hover:shadow-2xl transform hover:scale-105 transition-all duration-300 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-user-circle text-3xl"></i>
            </div>
            <h3 class="font-bold text-lg">Mein Profil</h3>
            <p class="text-sm text-white/80 mt-1">Profil anzeigen</p>
        </a>

        <a href="../inventory/index.php" class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white hover:shadow-2xl transform hover:scale-105 transition-all duration-300 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-boxes text-3xl"></i>
            </div>
            <h3 class="font-bold text-lg">Inventar</h3>
            <p class="text-sm text-white/80 mt-1">Artikel suchen</p>
        </a>

        <a href="../events/index.php" class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white hover:shadow-2xl transform hover:scale-105 transition-all duration-300 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-calendar text-3xl"></i>
            </div>
            <h3 class="font-bold text-lg">Events ansehen</h3>
            <p class="text-sm text-white/80 mt-1">Kommende Events</p>
        </a>

        <a href="../projects/index.php" class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white hover:shadow-2xl transform hover:scale-105 transition-all duration-300 text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-folder text-3xl"></i>
            </div>
            <h3 class="font-bold text-lg">Projekte</h3>
            <p class="text-sm text-white/80 mt-1">Projekte anzeigen</p>
        </a>
    </div>
</div>

<!-- Hero Section - Legacy (hidden, kept for reference during transition period) -->
<!-- TODO: Remove after verifying new layout is stable (2 weeks from deployment) -->
<div class="mb-12 hidden">
    <div class="text-center max-w-4xl mx-auto">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-800 mb-4">
            Willkommen im IBC Intranet
        </h1>
        <p class="text-lg md:text-xl text-gray-600 mb-8">
            Verwalten Sie Ihr Inventar effizient und behalten Sie alles im Blick
        </p>
        
        <!-- Primary Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
            <a href="../inventory/index.php" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl font-semibold text-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl">
                <i class="fas fa-boxes mr-3 text-xl"></i>
                Zum Inventar
            </a>
            <a href="../auth/profile.php" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl font-semibold text-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-2xl">
                <i class="fas fa-user mr-3 text-xl"></i>
                Mein Profil
            </a>
        </div>
    </div>
</div>

<!-- Dashboard Teaser - 3 Live Statistics Tiles -->
<div class="max-w-6xl mx-auto">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
        <i class="fas fa-chart-line text-purple-600 mr-2"></i>
        Aktuelle Statistiken
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <!-- Tile 1: Available Items -->
        <div class="card p-8 rounded-xl shadow-lg transition-all hover:shadow-2xl text-center bg-gradient-to-br from-white to-blue-50">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <i class="fas fa-box-open text-3xl text-blue-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Verfügbare Artikel</h3>
            <p class="text-4xl font-bold text-blue-600 mb-2"><?php echo number_format($stats['total_items']); ?></p>
            <p class="text-sm text-gray-500">Artikel im System</p>
        </div>
        
        <!-- Tile 2: Total Value -->
        <div class="card p-8 rounded-xl shadow-lg transition-all hover:shadow-2xl text-center bg-gradient-to-br from-white to-green-50">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <i class="fas fa-euro-sign text-3xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Gesamtwert</h3>
            <p class="text-4xl font-bold text-green-600 mb-2"><?php echo number_format($stats['total_value'], 2); ?> €</p>
            <p class="text-sm text-gray-500">Inventarwert</p>
        </div>
        
        <!-- Tile 3: Recent Activity -->
        <div class="card p-8 rounded-xl shadow-lg transition-all hover:shadow-2xl text-center bg-gradient-to-br from-white to-purple-50">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
                <i class="fas fa-clock text-3xl text-purple-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Letzte 7 Tage</h3>
            <p class="text-4xl font-bold text-purple-600 mb-2"><?php echo number_format($stats['recent_moves']); ?></p>
            <p class="text-sm text-gray-500">Bewegungen</p>
        </div>
    </div>
</div>

<!-- Additional Info Cards -->
<div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Quick Actions Card -->
    <div class="card p-6 rounded-xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-bolt text-yellow-500 mr-2"></i>
            Schnellaktionen
        </h3>
        <div class="space-y-3">
            <a href="../inventory/index.php" class="block p-4 rounded-lg bg-gradient-to-r from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 transition-all duration-300 group">
                <div class="flex items-center">
                    <i class="fas fa-boxes text-purple-600 mr-3 text-xl group-hover:scale-110 transition-transform"></i>
                    <span class="font-semibold text-gray-800">Inventar durchsuchen</span>
                </div>
            </a>
            <?php if (Auth::hasPermission('manager')): ?>
            <a href="../inventory/add.php" class="block p-4 rounded-lg bg-gradient-to-r from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 transition-all duration-300 group">
                <div class="flex items-center">
                    <i class="fas fa-plus-circle text-green-600 mr-3 text-xl group-hover:scale-110 transition-transform"></i>
                    <span class="font-semibold text-gray-800">Neuen Artikel hinzufügen</span>
                </div>
            </a>
            <?php endif; ?>
            <?php if (Auth::hasPermission('admin')): ?>
            <a href="../admin/users.php" class="block p-4 rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 transition-all duration-300 group">
                <div class="flex items-center">
                    <i class="fas fa-users-cog text-blue-600 mr-3 text-xl group-hover:scale-110 transition-transform"></i>
                    <span class="font-semibold text-gray-800">Benutzerverwaltung</span>
                </div>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Status Overview Card -->
    <div class="card p-6 rounded-xl shadow-lg">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
            Status-Übersicht
        </h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-3 text-xl"></i>
                    <span class="text-gray-700 font-medium">Niedriger Bestand</span>
                </div>
                <span class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['low_stock']); ?></span>
            </div>
            <?php if ($hasExtendedAccess && isset($inStockStats)): ?>
            <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50">
                <div class="flex items-center">
                    <i class="fas fa-warehouse text-green-500 mr-3 text-xl"></i>
                    <span class="text-gray-700 font-medium">Im Lager</span>
                </div>
                <span class="text-2xl font-bold text-green-600"><?php echo number_format($inStockStats['total_in_stock']); ?></span>
            </div>
            <?php endif; ?>
            <div class="p-4 rounded-lg bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200">
                <p class="text-sm text-gray-600">
                    <i class="fas fa-user-circle mr-2 text-purple-600"></i>
                    Angemeldet als <strong><?php echo htmlspecialchars($user['email']); ?></strong>
                </p>
            </div>
        </div>
    </div>
</div>

<?php if ($hasExtendedAccess): ?>
<!-- Extended Dashboard for Board/Managers -->

<!-- Write-off Warning Box (if any this month) -->
<?php if ($writeOffStats['total_writeoffs'] > 0): ?>
<div class="max-w-6xl mx-auto mt-8 p-6 bg-red-50 border-l-4 border-red-500 rounded-xl shadow-lg">
    <div class="flex items-center mb-4">
        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-red-800">Verlust/Defekt diesen Monat</h2>
            <p class="text-red-600"><?php echo $writeOffStats['total_writeoffs']; ?> Meldungen, <?php echo $writeOffStats['total_quantity_lost']; ?> Einheiten betroffen</p>
        </div>
    </div>
    <div class="bg-white rounded-lg p-4 max-h-80 overflow-y-auto shadow-lg">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Menge</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Gemeldet von</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Grund</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($writeOffStats['writeoffs'] as $writeoff): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 whitespace-nowrap">
                        <?php echo date('d.m.Y', strtotime($writeoff['timestamp'])); ?>
                    </td>
                    <td class="px-3 py-2">
                        <a href="../inventory/view.php?id=<?php echo $writeoff['item_id']; ?>" class="text-purple-600 hover:text-purple-800 font-medium">
                            <?php echo htmlspecialchars($writeoff['item_name']); ?>
                        </a>
                    </td>
                    <td class="px-3 py-2 whitespace-nowrap">
                        <span class="text-red-600 font-semibold"><?php echo abs($writeoff['change_amount']); ?> <?php echo htmlspecialchars($writeoff['unit']); ?></span>
                    </td>
                    <td class="px-3 py-2">
                        <?php echo htmlspecialchars($writeoff['reported_by_email']); ?>
                    </td>
                    <td class="px-3 py-2">
                        <?php echo htmlspecialchars($writeoff['comment'] ?? $writeoff['reason'] ?? '-'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- In Stock and On Route Tiles -->
<div class="max-w-6xl mx-auto mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Im Lager (In Stock) -->
    <div class="card p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-warehouse text-green-600 mr-2"></i>
            Im Lager
        </h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Gesamtbestand</p>
                    <p class="text-2xl font-bold text-green-700"><?php echo number_format($inStockStats['total_in_stock']); ?> Einheiten</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box-open text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Verschiedene Artikel</p>
                    <p class="text-2xl font-bold text-blue-700"><?php echo number_format($inStockStats['unique_items_in_stock']); ?> Artikel</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-boxes text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="flex items-center justify-between p-4 bg-purple-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Wert im Lager</p>
                    <p class="text-2xl font-bold text-purple-700"><?php echo number_format($inStockStats['total_value_in_stock'], 2); ?> €</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-euro-sign text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Unterwegs (On Route / Checked Out) -->
    <div class="card p-6 rounded-xl shadow-lg">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-truck text-orange-600 mr-2"></i>
            Unterwegs
        </h2>
        <?php if ($checkedOutStats['total_items_out'] > 0): ?>
        <div class="space-y-2 mb-4">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-600">Aktive Ausleihen</p>
                    <p class="text-xl font-bold text-orange-700"><?php echo count($checkedOutStats['checkouts']); ?> Ausleihen</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Entliehene Menge</p>
                    <p class="text-xl font-bold text-orange-700"><?php echo $checkedOutStats['total_items_out']; ?> Einheiten</p>
                </div>
            </div>
        </div>
        <div class="max-h-64 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Artikel</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Menge</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Entleiher</th>
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rückgabe</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($checkedOutStats['checkouts'] as $checkout): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-2">
                            <a href="../inventory/view.php?id=<?php echo $checkout['item_id']; ?>" class="text-purple-600 hover:text-purple-800 font-medium text-xs">
                                <?php echo htmlspecialchars($checkout['item_name']); ?>
                            </a>
                        </td>
                        <td class="px-2 py-2 whitespace-nowrap text-xs">
                            <?php echo $checkout['amount']; ?> <?php echo htmlspecialchars($checkout['unit']); ?>
                        </td>
                        <td class="px-2 py-2 text-xs">
                            <?php echo htmlspecialchars($checkout['borrower_email']); ?>
                        </td>
                        <td class="px-2 py-2 text-xs">
                            <?php echo date('d.m.Y', strtotime($checkout['expected_return'] ?? $checkout['rented_at'])); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
            <i class="fas fa-check-circle text-4xl mb-3 text-green-400"></i>
            <p class="text-gray-500">Keine aktiven Ausleihen</p>
            <p class="text-sm text-gray-400 mt-1">Alle Artikel sind im Lager</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
