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

// Get filter from query parameters
$filter = $_GET['filter'] ?? 'current';

$filters = [];
$now = date('Y-m-d H:i:s');

// Filter logic
if ($filter === 'current') {
    // Show only future and current events
    $filters['start_date'] = $now;
} elseif ($filter === 'my_registrations') {
    // We'll filter this separately after getting events
}

// Get all events visible to user
$events = Event::getEvents($filters, $userRole);

// Get user's registrations if needed
if ($filter === 'my_registrations') {
    $userSignups = Event::getUserSignups($user['id']);
    $myEventIds = array_column($userSignups, 'event_id');
    $events = array_filter($events, function($event) use ($myEventIds) {
        return in_array($event['id'], $myEventIds);
    });
} else {
    // Hide past events for normal users (non-admin)
    if (!in_array($userRole, ['admin', 'board', 'alumni_board', 'manager'])) {
        $events = array_filter($events, function($event) use ($now) {
            return $event['end_time'] >= $now;
        });
    }
}

// Get user's signups for display
$userSignups = Event::getUserSignups($user['id']);
$myEventIds = array_column($userSignups, 'event_id');

$title = 'Events - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">
            <i class="fas fa-calendar-alt mr-3 text-purple-600"></i>
            Events
        </h1>
        <p class="text-gray-600">Entdecken Sie kommende Events und melden Sie sich an</p>
    </div>

    <!-- Filter Tabs -->
    <div class="mb-6 flex gap-2 flex-wrap">
        <a href="?filter=current" 
           class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $filter === 'current' ? 'bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
            <i class="fas fa-calendar-day mr-2"></i>
            Aktuell
        </a>
        <a href="?filter=my_registrations" 
           class="px-6 py-3 rounded-lg font-semibold transition-all <?php echo $filter === 'my_registrations' ? 'bg-gradient-to-r from-purple-600 to-purple-700 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
            <i class="fas fa-user-check mr-2"></i>
            Meine Anmeldungen
        </a>
    </div>

    <!-- Events Grid -->
    <?php if (empty($events)): ?>
        <div class="card p-8 text-center">
            <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-600">Keine Events gefunden</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($events as $event): ?>
                <?php
                    // Calculate countdown for upcoming events
                    $startTimestamp = strtotime($event['start_time']);
                    $nowTimestamp = time();
                    $isUpcoming = $startTimestamp > $nowTimestamp;
                    $isPast = strtotime($event['end_time']) < $nowTimestamp;
                    $isRegistered = in_array($event['id'], $myEventIds);
                    
                    $countdown = '';
                    if ($isUpcoming) {
                        $diff = $startTimestamp - $nowTimestamp;
                        $days = floor($diff / 86400);
                        $hours = floor(($diff % 86400) / 3600);
                        
                        if ($days > 0) {
                            $countdown = "Noch {$days} Tag" . ($days != 1 ? 'e' : '') . ", {$hours} Std";
                        } else {
                            $countdown = "Noch {$hours} Std";
                        }
                    }
                ?>
                
                <div class="card p-6 relative">
                    <!-- Status Badge -->
                    <?php if ($isRegistered): ?>
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                <i class="fas fa-check mr-1"></i>
                                Angemeldet
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Event Header -->
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($event['title']); ?>
                        </h3>
                        
                        <!-- Countdown -->
                        <?php if ($countdown): ?>
                            <div class="mb-2">
                                <span class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-800 text-sm font-semibold rounded-lg">
                                    <i class="fas fa-clock mr-2"></i>
                                    <?php echo $countdown; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Event Info -->
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-start">
                                <i class="fas fa-calendar w-5 mt-0.5 text-purple-600"></i>
                                <span>
                                    <?php 
                                        $startDate = new DateTime($event['start_time']);
                                        $endDate = new DateTime($event['end_time']);
                                        echo $startDate->format('d.m.Y H:i') . ' - ' . $endDate->format('H:i');
                                    ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($event['location'])): ?>
                                <div class="flex items-start">
                                    <i class="fas fa-map-marker-alt w-5 mt-0.5 text-purple-600"></i>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event['is_external']): ?>
                                <div class="flex items-start">
                                    <i class="fas fa-external-link-alt w-5 mt-0.5 text-blue-600"></i>
                                    <span class="text-blue-600">Externes Event</span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event['needs_helpers'] && $userRole !== 'alumni'): ?>
                                <div class="flex items-start">
                                    <i class="fas fa-hands-helping w-5 mt-0.5 text-orange-600"></i>
                                    <span class="text-orange-600">Helfer benötigt</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Description Preview -->
                    <?php if (!empty($event['description'])): ?>
                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>
                            <?php echo strlen($event['description']) > 150 ? '...' : ''; ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Action Button -->
                    <a href="view.php?id=<?php echo $event['id']; ?>" 
                       class="block w-full text-center px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg font-semibold hover:from-purple-700 hover:to-purple-800 transition-all">
                        <i class="fas fa-eye mr-2"></i>
                        Details ansehen
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
