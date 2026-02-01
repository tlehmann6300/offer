<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';

AuthHandler::startSession();

if (!AuthHandler::isAuthenticated()) {
    header('Location: ../auth/login.php');
    exit;
}

// Get filters
$filters = [];
if (!empty($_GET['category_id'])) {
    $filters['category_id'] = $_GET['category_id'];
}
if (!empty($_GET['location_id'])) {
    $filters['location_id'] = $_GET['location_id'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['filter']) && $_GET['filter'] === 'low_stock') {
    $filters['low_stock'] = true;
}

$items = Inventory::getAll($filters);
$categories = Inventory::getCategories();
$locations = Inventory::getLocations();

$title = 'Inventar - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-boxes text-purple-600 mr-2"></i>
                Inventar
            </h1>
            <p class="text-gray-600"><?php echo count($items); ?> Artikel gefunden</p>
        </div>
        <?php if (AuthHandler::hasPermission('manager')): ?>
        <div class="mt-4 md:mt-0">
            <a href="add.php" class="btn-primary inline-block">
                <i class="fas fa-plus mr-2"></i>
                Neuer Artikel
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Suche</label>
            <input 
                type="text" 
                name="search" 
                placeholder="Name oder Beschreibung..."
                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Kategorie</label>
            <select 
                name="category_id" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
                <option value="">Alle Kategorien</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Standort</label>
            <select 
                name="location_id" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
                <option value="">Alle Standorte</option>
                <?php foreach ($locations as $location): ?>
                <option value="<?php echo $location['id']; ?>" <?php echo (isset($_GET['location_id']) && $_GET['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($location['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end space-x-2">
            <button type="submit" class="flex-1 btn-primary">
                <i class="fas fa-search mr-2"></i>Filtern
            </button>
            <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Items Grid (Mobile-First Card Layout) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php if (empty($items)): ?>
    <div class="col-span-full card p-12 text-center">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 text-lg">Keine Artikel gefunden</p>
        <?php if (AuthHandler::hasPermission('manager')): ?>
        <a href="add.php" class="btn-primary inline-block mt-4">
            <i class="fas fa-plus mr-2"></i>Ersten Artikel hinzufügen
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <?php foreach ($items as $item): ?>
    <div class="card overflow-hidden card-hover">
        <!-- Image -->
        <div class="h-48 bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center">
            <?php if ($item['image_path']): ?>
            <img src="/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
            <i class="fas fa-box-open text-6xl text-purple-300"></i>
            <?php endif; ?>
        </div>

        <!-- Content -->
        <div class="p-4">
            <h3 class="font-bold text-lg text-gray-800 mb-2 line-clamp-2"><?php echo htmlspecialchars($item['name']); ?></h3>
            
            <!-- Category & Location -->
            <div class="flex flex-wrap gap-2 mb-3">
                <?php if ($item['category_name']): ?>
                <span class="px-2 py-1 text-xs rounded-full" style="background-color: <?php echo htmlspecialchars($item['category_color']); ?>20; color: <?php echo htmlspecialchars($item['category_color']); ?>">
                    <?php echo htmlspecialchars($item['category_name']); ?>
                </span>
                <?php endif; ?>
                <?php if ($item['location_name']): ?>
                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                    <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($item['location_name']); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Stock Info -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-600">Bestand:</span>
                    <span class="font-bold text-lg <?php echo $item['current_stock'] <= $item['min_stock'] && $item['min_stock'] > 0 ? 'text-red-600' : 'text-gray-800'; ?>">
                        <?php echo $item['current_stock']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                    </span>
                </div>
                <?php if ($item['current_stock'] <= $item['min_stock'] && $item['min_stock'] > 0): ?>
                <div class="text-xs text-red-600 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Unter Mindestbestand (<?php echo $item['min_stock']; ?>)
                </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="flex space-x-2">
                <a href="view.php?id=<?php echo $item['id']; ?>" class="flex-1 text-center px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                    <i class="fas fa-eye mr-1"></i>Details
                </a>
                <?php if (AuthHandler::hasPermission('manager')): ?>
                <a href="edit.php?id=<?php echo $item['id']; ?>" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-edit"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
