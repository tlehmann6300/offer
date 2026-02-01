<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';

AuthHandler::startSession();

// Check authentication
if (!AuthHandler::isAuthenticated()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = AuthHandler::getCurrentUser();
$stats = Inventory::getDashboardStats();

// Get extended statistics for board/managers
$hasExtendedAccess = AuthHandler::hasPermission('manager');
if ($hasExtendedAccess) {
    $inStockStats = Inventory::getInStockStats();
    $checkedOutStats = Inventory::getCheckedOutStats();
    $writeOffStats = Inventory::getWriteOffStatsThisMonth();
}

$title = 'Dashboard - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-2">
        <i class="fas fa-home text-purple-600 mr-2"></i>
        Dashboard
    </h1>
    <p class="text-gray-600">Willkommen zurück, <?php echo htmlspecialchars($user['email']); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Items -->
    <div class="card p-6 card-hover transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium mb-1">Gesamte Artikel</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_items']); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-boxes text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Value -->
    <div class="card p-6 card-hover transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium mb-1">Gesamtwert</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_value'], 2); ?> €</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-euro-sign text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Low Stock -->
    <div class="card p-6 card-hover transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium mb-1">Niedriger Bestand</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['low_stock']); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
            </div>
        </div>
        <?php if ($stats['low_stock'] > 0): ?>
        <a href="../inventory/index.php?filter=low_stock" class="text-yellow-600 text-sm mt-2 inline-block hover:underline">
            Ansehen <i class="fas fa-arrow-right ml-1"></i>
        </a>
        <?php endif; ?>
    </div>

    <!-- Recent Moves -->
    <div class="card p-6 card-hover transition-all">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium mb-1">Letzte 7 Tage</p>
                <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['recent_moves']); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exchange-alt text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<?php if ($hasExtendedAccess): ?>
<!-- Extended Dashboard for Board/Managers -->

<!-- Write-off Warning Box (if any this month) -->
<?php if ($writeOffStats['total_writeoffs'] > 0): ?>
<div class="mb-8 p-6 bg-red-50 border-l-4 border-red-500 rounded-lg">
    <div class="flex items-center mb-4">
        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
            <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
        </div>
        <div>
            <h2 class="text-xl font-bold text-red-800">Verlust/Defekt diesen Monat</h2>
            <p class="text-red-600"><?php echo $writeOffStats['total_writeoffs']; ?> Meldungen, <?php echo $writeOffStats['total_quantity_lost']; ?> Einheiten betroffen</p>
        </div>
    </div>
    <div class="bg-white rounded-lg p-4 max-h-80 overflow-y-auto">
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
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Im Lager (In Stock) -->
    <div class="card p-6">
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
    <div class="card p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-truck text-orange-600 mr-2"></i>
            Unterwegs
        </h2>
        <?php if ($checkedOutStats['total_checked_out'] > 0): ?>
        <div class="space-y-2 mb-4">
            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-600">Aktive Ausleihen</p>
                    <p class="text-xl font-bold text-orange-700"><?php echo $checkedOutStats['total_checked_out']; ?> Ausleihen</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Entliehene Menge</p>
                    <p class="text-xl font-bold text-orange-700"><?php echo $checkedOutStats['total_quantity_out']; ?> Einheiten</p>
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
                        <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase">Zielort</th>
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
                            <?php echo $checkout['quantity']; ?> <?php echo htmlspecialchars($checkout['unit']); ?>
                        </td>
                        <td class="px-2 py-2 text-xs">
                            <?php echo htmlspecialchars($checkout['borrower_email']); ?>
                        </td>
                        <td class="px-2 py-2 text-xs">
                            <?php echo htmlspecialchars($checkout['destination'] ?? '-'); ?>
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

<!-- Quick Actions -->
<div class="card p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
        Schnellaktionen
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="../inventory/index.php" class="btn-primary text-center block transition-all">
            <i class="fas fa-boxes mr-2"></i>
            Inventar anzeigen
        </a>
        <?php if (AuthHandler::hasPermission('manager')): ?>
        <a href="../inventory/add.php" class="btn-primary text-center block transition-all">
            <i class="fas fa-plus mr-2"></i>
            Artikel hinzufügen
        </a>
        <?php endif; ?>
        <a href="../auth/profile.php" class="btn-primary text-center block transition-all">
            <i class="fas fa-user mr-2"></i>
            Mein Profil
        </a>
        <?php if (AuthHandler::hasPermission('admin')): ?>
        <a href="../admin/users.php" class="btn-primary text-center block transition-all">
            <i class="fas fa-users mr-2"></i>
            Benutzerverwaltung
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activity (Placeholder) -->
<div class="card p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-clock text-blue-500 mr-2"></i>
        Letzte Aktivitäten
    </h2>
    <div class="text-gray-500 text-center py-8">
        <i class="fas fa-history text-4xl mb-3 text-gray-300"></i>
        <p>Keine aktuellen Aktivitäten</p>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
