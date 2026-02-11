<?php
/**
 * Polls - List all active polls
 * Access: All authenticated users (filtered by target_groups)
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
$userRole = $user['role'] ?? '';

// Get database connection
$db = Database::getContentDB();

// Fetch all active polls
$stmt = $db->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM poll_votes WHERE poll_id = p.id AND user_id = ?) as user_has_voted,
           (SELECT COUNT(*) FROM poll_votes WHERE poll_id = p.id) as total_votes
    FROM polls p
    WHERE p.is_active = 1 AND p.end_date > NOW()
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$polls = $stmt->fetchAll();

// Filter polls by target_groups (user role must be in the JSON array)
$filteredPolls = array_filter($polls, function($poll) use ($userRole) {
    $targetGroups = json_decode($poll['target_groups'], true);
    return in_array($userRole, $targetGroups);
});

$title = 'Umfragen - IBC Intranet';
ob_start();
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                <i class="fas fa-poll mr-3 text-blue-500"></i>
                Umfragen
            </h1>
            <p class="text-gray-600 dark:text-gray-300">Aktive Umfragen für Ihre Rolle</p>
        </div>
        
        <?php if (Auth::hasRole(['head', 'board', 'vorstand_intern', 'vorstand_extern', 'vorstand_finanzen_recht'])): ?>
        <a 
            href="<?php echo asset('pages/polls/create.php'); ?>"
            class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg"
        >
            <i class="fas fa-plus mr-2"></i>
            Umfrage erstellen
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($filteredPolls)): ?>
    <!-- No polls message -->
    <div class="card p-8 text-center">
        <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-poll text-gray-400 dark:text-gray-500 text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">Keine aktiven Umfragen</h3>
        <p class="text-gray-600 dark:text-gray-300">Es sind derzeit keine Umfragen für Sie verfügbar.</p>
    </div>
    <?php else: ?>
    
    <!-- Polls Grid -->
    <div class="grid gap-6">
        <?php foreach ($filteredPolls as $poll): ?>
        <div class="card p-6 hover:shadow-lg transition-shadow">
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                        <?php echo htmlspecialchars($poll['title']); ?>
                    </h3>
                    <?php if (!empty($poll['description'])): ?>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        <?php echo nl2br(htmlspecialchars($poll['description'])); ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <!-- Status Badge -->
                <?php if ($poll['user_has_voted'] > 0): ?>
                <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded-full text-sm font-semibold">
                    <i class="fas fa-check-circle mr-1"></i>
                    Abgestimmt
                </span>
                <?php else: ?>
                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 rounded-full text-sm font-semibold">
                    <i class="fas fa-clock mr-1"></i>
                    Offen
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Meta Information -->
            <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                <div>
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Endet am <?php echo formatDateTime($poll['end_date'], 'd.m.Y H:i'); ?> Uhr
                </div>
                <div>
                    <i class="fas fa-users mr-1"></i>
                    <?php echo $poll['total_votes']; ?> Stimme(n)
                </div>
            </div>
            
            <!-- Action Button -->
            <a 
                href="<?php echo asset('pages/polls/view.php?id=' . $poll['id']); ?>"
                class="inline-block px-6 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors"
            >
                <?php if ($poll['user_has_voted'] > 0): ?>
                    <i class="fas fa-chart-bar mr-2"></i>Ergebnisse ansehen
                <?php else: ?>
                    <i class="fas fa-vote-yea mr-2"></i>Jetzt abstimmen
                <?php endif; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
