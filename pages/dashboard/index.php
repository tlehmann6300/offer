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
