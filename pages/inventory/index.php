<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';

if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle JSON import
$importMessage = null;
$importSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_json']) && Auth::hasPermission('manager')) {
    if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
        $jsonContent = file_get_contents($_FILES['json_file']['tmp_name']);
        $data = json_decode($jsonContent, true);
        
        if ($data === null) {
            $importMessage = 'Fehler: Ungültiges JSON-Format';
            $importSuccess = false;
        } else {
            $result = Inventory::importFromJson($data, $_SESSION['user_id']);
            
            if ($result['success']) {
                $importMessage = "Import erfolgreich! {$result['imported']} Artikel importiert";
                if ($result['skipped'] > 0) {
                    $importMessage .= ", {$result['skipped']} übersprungen";
                }
                $importSuccess = true;
            } else {
                $importMessage = "Import fehlgeschlagen";
                $importSuccess = false;
            }
            
            // Store errors in session for display
            if (!empty($result['errors'])) {
                $_SESSION['import_errors'] = $result['errors'];
            }
        }
    } else {
        $importMessage = 'Fehler: Keine Datei hochgeladen oder Upload-Fehler';
        $importSuccess = false;
    }
}

// Get import errors from session and clear them
$importErrors = $_SESSION['import_errors'] ?? [];
unset($_SESSION['import_errors']);

// Get sync results from session and clear them
$syncResult = $_SESSION['sync_result'] ?? null;
unset($_SESSION['sync_result']);

// Get filters
$filters = [];
if (!empty($_GET['category_id'])) {
    $filters['category_id'] = $_GET['category_id'];
}
if (!empty($_GET['location'])) {
    $filters['location'] = $_GET['location'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['filter']) && $_GET['filter'] === 'low_stock') {
    $filters['low_stock'] = true;
}

// Get sort parameter
$sort = $_GET['sort'] ?? 'name_asc';
$filters['sort'] = $sort;

$items = Inventory::getAll($filters);
$categories = Inventory::getCategories();
$locations = Inventory::getLocations();

// Get distinct locations dynamically for the filter dropdown
$db = Database::getContentDB();
$locationsQuery = $db->query("
    SELECT DISTINCT location 
    FROM inventory_items 
    WHERE location IS NOT NULL AND location != '' 
    ORDER BY location ASC
");
$distinctLocations = $locationsQuery->fetchAll(PDO::FETCH_COLUMN);

$title = 'Inventar - IBC Intranet';
ob_start();
?>

<div class="mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                <i class="fas fa-boxes text-purple-600 mr-2"></i>
                Inventar
            </h1>
            <p class="text-gray-600 dark:text-gray-300"><?php echo count($items); ?> Artikel gefunden</p>
        </div>
        <!-- Action Buttons -->
        <div class="mt-4 md:mt-0 flex gap-2">
            <!-- Neuer Artikel Button -->
            <a href="add.php" class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Neuer Artikel
            </a>
            <!-- EasyVerein Sync Button - Admin/Board only -->
            <?php if (AuthHandler::isAdmin()): ?>
            <a href="sync.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-sync-alt mr-2"></i> EasyVerein Sync
            </a>
            <?php endif; ?>
            <!-- Import Button - Manager level and above -->
            <?php if (Auth::hasPermission('manager')): ?>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-file-import mr-2"></i>
                Massenimport
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Import Success/Error Messages -->
<?php if ($importMessage): ?>
<div class="mb-6 p-4 rounded-lg <?php echo $importSuccess ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
    <div class="flex items-start">
        <i class="fas <?php echo $importSuccess ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 mt-1"></i>
        <div class="flex-1">
            <p class="font-semibold"><?php echo htmlspecialchars($importMessage); ?></p>
            <?php if (!empty($importErrors)): ?>
            <details class="mt-2">
                <summary class="cursor-pointer text-sm underline">Details anzeigen (<?php echo count($importErrors); ?> Fehler)</summary>
                <ul class="mt-2 list-disc list-inside text-sm">
                    <?php foreach ($importErrors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Sync Results -->
<?php if ($syncResult): ?>
<div class="mb-6 p-4 rounded-lg bg-blue-100 border border-blue-400 text-blue-700">
    <div class="flex items-start">
        <i class="fas fa-sync-alt mr-3 mt-1"></i>
        <div class="flex-1">
            <p class="font-semibold">EasyVerein Synchronisierung abgeschlossen</p>
            <ul class="mt-2 text-sm">
                <li>✓ Erstellt: <?php echo htmlspecialchars($syncResult['created']); ?> Artikel</li>
                <li>✓ Aktualisiert: <?php echo htmlspecialchars($syncResult['updated']); ?> Artikel</li>
                <li>✓ Archiviert: <?php echo htmlspecialchars($syncResult['archived']); ?> Artikel</li>
            </ul>
            <?php if (!empty($syncResult['errors'])): ?>
            <details class="mt-2">
                <summary class="cursor-pointer text-sm underline">Fehler anzeigen (<?php echo count($syncResult['errors']); ?>)</summary>
                <ul class="mt-2 list-disc list-inside text-sm">
                    <?php foreach ($syncResult['errors'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Import Modal -->
<?php if (Auth::hasPermission('manager')): ?>
<div id="importModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
                <i class="fas fa-file-import text-green-600 mr-2"></i>
                Inventar Massenimport
            </h2>
            <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">JSON-Datei auswählen</label>
                <input 
                    type="file" 
                    name="json_file" 
                    accept=".json,application/json"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-gray-200"
                >
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Laden Sie eine JSON-Datei mit Inventar-Artikeln hoch
                </p>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>JSON-Format
                </h3>
                <p class="text-sm text-blue-800 dark:text-blue-300 mb-2">Die JSON-Datei sollte ein Array von Objekten enthalten:</p>
                <pre class="text-xs bg-white dark:bg-gray-900 dark:text-gray-300 p-3 rounded border border-blue-200 dark:border-blue-800 overflow-x-auto"><code>[
  {
    "name": "Laptop Dell XPS 15",
    "category": "IT-Equipment",
    "status": "available",
    "description": "15 Zoll Laptop",
    "serial_number": "DXPS123456",
    "location": "Büro München",
    "purchase_date": "2024-01-15"
  }
]</code></pre>
                <div class="mt-3 text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-semibold">Pflichtfelder:</p>
                    <ul class="list-disc list-inside ml-2">
                        <li><strong>name</strong>: Name des Artikels</li>
                        <li><strong>category</strong>: Kategorie (wird erstellt, falls nicht vorhanden)</li>
                    </ul>
                    <p class="font-semibold mt-2">Optionale Felder:</p>
                    <ul class="list-disc list-inside ml-2">
                        <li><strong>status</strong>: Status (available, in_use, maintenance, retired) - Standard: "available"</li>
                        <li><strong>description</strong>: Beschreibung</li>
                        <li><strong>serial_number</strong>: Seriennummer (muss eindeutig sein)</li>
                        <li><strong>location</strong>: Standort (wird erstellt, falls nicht vorhanden)</li>
                        <li><strong>purchase_date</strong>: Kaufdatum (Format: YYYY-MM-DD)</li>
                    </ul>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button 
                    type="button" 
                    onclick="document.getElementById('importModal').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                >
                    Abbrechen
                </button>
                <button 
                    type="submit" 
                    name="import_json"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                    <i class="fas fa-upload mr-2"></i>
                    Importieren
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card p-6 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suche</label>
            <input 
                type="text" 
                name="search" 
                placeholder="Name oder Beschreibung..."
                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-gray-200"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Standort</label>
            <select 
                name="location" 
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-gray-200"
            >
                <option value="">Alle Standorte</option>
                <?php foreach ($distinctLocations as $location): ?>
                <option value="<?php echo htmlspecialchars($location); ?>" <?php echo (isset($_GET['location']) && $_GET['location'] == $location) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($location); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sortieren nach</label>
            <select 
                name="sort" 
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-gray-200"
            >
                <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                <option value="quantity_desc" <?php echo ($sort == 'quantity_desc') ? 'selected' : ''; ?>>Menge (Hoch-Niedrig)</option>
                <option value="quantity_asc" <?php echo ($sort == 'quantity_asc') ? 'selected' : ''; ?>>Menge (Niedrig-Hoch)</option>
                <option value="price_desc" <?php echo ($sort == 'price_desc') ? 'selected' : ''; ?>>Preis (Hoch-Niedrig)</option>
                <option value="price_asc" <?php echo ($sort == 'price_asc') ? 'selected' : ''; ?>>Preis (Niedrig-Hoch)</option>
            </select>
        </div>
        <div class="flex items-end space-x-2">
            <button type="submit" class="flex-1 btn-primary">
                <i class="fas fa-search mr-2"></i>Filtern
            </button>
            <a href="index.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Items Table Layout -->
<div class="card overflow-hidden">
    <?php if (empty($items)): ?>
    <div class="p-12 text-center">
        <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
        <p class="text-gray-500 dark:text-gray-400 text-lg">Keine Artikel gefunden</p>
        <?php if (AuthHandler::isAdmin()): ?>
        <a href="sync.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center mt-4">
            <i class="fas fa-sync-alt mr-2"></i> EasyVerein Sync
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bild</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Artikel</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Anzahl</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Preis</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lagerort</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aktionen</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($items as $item): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 <?php echo $item['is_archived_in_easyverein'] ? 'opacity-60' : ''; ?>">
                    <!-- Image -->
                    <td class="px-4 py-4 whitespace-nowrap">
                        <div class="h-16 w-16 flex items-center justify-center bg-gradient-to-br from-purple-100 to-blue-100 rounded <?php echo $item['is_archived_in_easyverein'] ? 'grayscale' : ''; ?>" <?php if (empty($item['image_path'])): ?>aria-label="Kein Bild verfügbar"<?php endif; ?>>
                            <?php if (!empty($item['image_path'])): ?>
                            <img src="/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-full w-full object-cover rounded">
                            <?php else: ?>
                            <i class="fas fa-image text-gray-400 dark:text-gray-500 text-2xl" aria-hidden="true"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    
                    <!-- Artikel -->
                    <td class="px-4 py-4">
                        <div class="flex flex-col">
                            <span class="font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($item['name']); ?></span>
                            <?php if ($item['category_name']): ?>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($item['category_name']); ?>
                            </span>
                            <?php endif; ?>
                            <div class="flex gap-1 mt-1">
                                <?php if (!empty($item['easyverein_id'])): ?>
                                <span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded" title="Synchronized with EasyVerein">
                                    <i class="fas fa-sync-alt"></i>
                                </span>
                                <?php endif; ?>
                                <?php if ($item['is_archived_in_easyverein']): ?>
                                <span class="px-1.5 py-0.5 text-xs bg-red-100 text-red-700 rounded" title="Archived in EasyVerein">
                                    <i class="fas fa-archive"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    
                    <!-- Anzahl -->
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="font-semibold <?php echo $item['quantity'] <= $item['min_stock'] && $item['min_stock'] > 0 ? 'text-red-600' : 'text-gray-900 dark:text-gray-100'; ?>">
                            <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?>
                        </span>
                        <?php if ($item['quantity'] <= $item['min_stock'] && $item['min_stock'] > 0): ?>
                        <div class="text-xs text-red-600 flex items-center mt-1">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Niedrig
                        </div>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Preis -->
                    <td class="px-4 py-4 whitespace-nowrap">
                        <span class="text-gray-900 dark:text-gray-100"><?php echo number_format($item['unit_price'], 2, ',', '.') . ' €'; ?></span>
                    </td>
                    
                    <!-- Lagerort -->
                    <td class="px-4 py-4 whitespace-nowrap">
                        <?php if ($item['location_name']): ?>
                        <span class="text-gray-700 dark:text-gray-300">
                            <i class="fas fa-map-marker-alt mr-1 text-gray-400 dark:text-gray-500"></i><?php echo htmlspecialchars($item['location_name']); ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-400 dark:text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Aktionen -->
                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                        <div class="flex space-x-2">
                            <a href="view.php?id=<?php echo $item['id']; ?>" class="text-purple-600 hover:text-purple-900" title="Details anzeigen">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (Auth::hasPermission('manager')): ?>
                            <a href="edit.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Bearbeiten">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
