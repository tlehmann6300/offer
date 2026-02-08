<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';
require_once __DIR__ . '/../../includes/models/Project.php';

// Check authentication and authorization
// Accessible only to: Board, Head, Alumni, Alumni-Board
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? '';

// Check if user has one of the allowed roles
$allowedRoles = ['admin', 'board', 'head', 'alumni', 'alumni_board'];
if (!in_array($userRole, $allowedRoles)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Get databases
$userDb = Database::getUserDB();
$contentDb = Database::getContentDB();

// Metric 1: Active Users (7 Days)
$stmt = $userDb->query("
    SELECT COUNT(*) as active_users 
    FROM users 
    WHERE last_login IS NOT NULL 
    AND last_login > DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$activeUsersCount = $stmt->fetch()['active_users'] ?? 0;

// Metric 2: Open Invitations Count
$stmt = $userDb->query("
    SELECT COUNT(*) as open_invitations 
    FROM invitation_tokens 
    WHERE expires_at > NOW()
");
$openInvitationsCount = $stmt->fetch()['open_invitations'] ?? 0;

// Metric 3: Total User Count
$stmt = $userDb->query("SELECT COUNT(*) as total_users FROM users");
$totalUsersCount = $stmt->fetch()['total_users'] ?? 0;

// List 1: 'Wer hat was ausgeliehen?' (Active Checkouts with User Names)
$activeCheckouts = [];
$stmt = $contentDb->query("
    SELECT 
        ic.id,
        ic.item_id,
        ic.user_id,
        ic.checked_out_at,
        ic.due_date,
        i.name as item_name,
        i.quantity as total_quantity
    FROM inventory_checkouts ic
    JOIN inventory_items i ON ic.item_id = i.id
    WHERE ic.returned_at IS NULL
    ORDER BY ic.checked_out_at DESC
");
$checkouts = $stmt->fetchAll();

// Get user information for checkouts
foreach ($checkouts as $checkout) {
    $userStmt = $userDb->prepare("SELECT id, email, firstname, lastname FROM users WHERE id = ?");
    $userStmt->execute([$checkout['user_id']]);
    $userInfo = $userStmt->fetch();
    
    $userName = 'Unbekannt';
    if ($userInfo) {
        if (!empty($userInfo['firstname']) && !empty($userInfo['lastname'])) {
            $userName = $userInfo['firstname'] . ' ' . $userInfo['lastname'];
        } elseif (!empty($userInfo['firstname'])) {
            $userName = $userInfo['firstname'];
        } elseif (!empty($userInfo['email'])) {
            $userName = explode('@', $userInfo['email'])[0];
        }
    }
    
    $activeCheckouts[] = [
        'checkout_id' => $checkout['id'],
        'item_name' => $checkout['item_name'],
        'user_name' => $userName,
        'user_email' => $userInfo['email'] ?? '',
        'checked_out_at' => $checkout['checked_out_at'],
        'due_date' => $checkout['due_date'],
        'is_overdue' => !empty($checkout['due_date']) && strtotime($checkout['due_date']) < time()
    ];
}

// List 2: 'Projekt Bewerbungen' (Count per Project)
$stmt = $contentDb->query("
    SELECT 
        p.id,
        p.title,
        p.type,
        p.status,
        COUNT(pa.id) as application_count
    FROM projects p
    LEFT JOIN project_applications pa ON p.id = pa.project_id
    WHERE p.status != 'draft'
    GROUP BY p.id, p.title, p.type, p.status
    ORDER BY p.created_at DESC
");
$projectApplications = $stmt->fetchAll();

$title = 'Statistiken - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">
            <i class="fas fa-chart-bar mr-3 text-purple-600"></i>
            Statistiken
        </h1>
        <p class="text-gray-600">Übersicht über wichtige Kennzahlen und Aktivitäten</p>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Active Users (7 Days) -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-blue-50 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 uppercase mb-1">Aktive Nutzer</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo number_format($activeUsersCount); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Letzte 7 Tage</p>
                </div>
                <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Open Invitations -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-green-50 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 uppercase mb-1">Offene Einladungen</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo number_format($openInvitationsCount); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Nicht verwendet</p>
                </div>
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-envelope-open-text text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="card p-6 rounded-xl shadow-lg bg-gradient-to-br from-white to-purple-50 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-600 uppercase mb-1">Gesamtanzahl User</h3>
                    <p class="text-3xl font-bold text-purple-600"><?php echo number_format($totalUsersCount); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Registriert</p>
                </div>
                <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-friends text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Checkouts Section -->
    <div class="card mb-8">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-box-open mr-2 text-orange-600"></i>
                Wer hat was ausgeliehen?
            </h2>
            <p class="text-sm text-gray-600 mt-1">Aktive Ausleihen mit Benutzernamen</p>
        </div>
        <div class="p-6">
            <?php if (empty($activeCheckouts)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 text-lg">Keine aktiven Ausleihen</p>
                    <p class="text-sm text-gray-500 mt-2">Alle Artikel wurden zurückgegeben</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Artikel
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Benutzer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ausgeliehen am
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fällig am
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($activeCheckouts as $checkout): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($checkout['item_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium">
                                        <?php echo htmlspecialchars($checkout['user_name']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($checkout['user_email']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo date('d.m.Y H:i', strtotime($checkout['checked_out_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php 
                                    if (!empty($checkout['due_date'])) {
                                        echo date('d.m.Y', strtotime($checkout['due_date']));
                                    } else {
                                        echo '<span class="text-gray-400">Kein Datum</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($checkout['is_overdue']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Überfällig
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Aktiv
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <strong>Gesamt:</strong> <?php echo count($activeCheckouts); ?> aktive Ausleihen
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Applications Section -->
    <div class="card">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-briefcase mr-2 text-purple-600"></i>
                Projekt Bewerbungen
            </h2>
            <p class="text-sm text-gray-600 mt-1">Anzahl Bewerbungen pro Projekt</p>
        </div>
        <div class="p-6">
            <?php if (empty($projectApplications)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-briefcase text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-600 text-lg">Keine Projekte vorhanden</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Projekttitel
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Typ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Bewerbungen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($projectApplications as $project): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <a href="../projects/view.php?id=<?php echo $project['id']; ?>" 
                                       class="text-purple-600 hover:text-purple-800 hover:underline">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php 
                                    $typeLabels = [
                                        'internal' => 'Intern',
                                        'external' => 'Extern'
                                    ];
                                    $typeColors = [
                                        'internal' => 'bg-blue-100 text-blue-800',
                                        'external' => 'bg-green-100 text-green-800'
                                    ];
                                    $typeLabel = $typeLabels[$project['type']] ?? $project['type'];
                                    $typeColor = $typeColors[$project['type']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $typeColor; ?>">
                                        <?php echo htmlspecialchars($typeLabel); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php 
                                    $statusLabels = [
                                        'open' => 'Offen',
                                        'assigned' => 'Zugewiesen',
                                        'running' => 'Läuft',
                                        'completed' => 'Abgeschlossen',
                                        'archived' => 'Archiviert'
                                    ];
                                    $statusColors = [
                                        'open' => 'bg-yellow-100 text-yellow-800',
                                        'assigned' => 'bg-blue-100 text-blue-800',
                                        'running' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-gray-100 text-gray-800',
                                        'archived' => 'bg-gray-100 text-gray-600'
                                    ];
                                    $statusLabel = $statusLabels[$project['status']] ?? $project['status'];
                                    $statusColor = $statusColors[$project['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                        <?php echo htmlspecialchars($statusLabel); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-lg font-bold text-purple-600 mr-2">
                                            <?php echo $project['application_count']; ?>
                                        </span>
                                        <?php if ($project['application_count'] > 0): ?>
                                        <a href="../projects/applications.php?project_id=<?php echo $project['id']; ?>" 
                                           class="text-xs text-purple-600 hover:text-purple-800 hover:underline">
                                            Details →
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-sm text-gray-600">
                    <strong>Gesamt:</strong> <?php echo count($projectApplications); ?> Projekte | 
                    <?php echo array_sum(array_column($projectApplications, 'application_count')); ?> Bewerbungen insgesamt
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
