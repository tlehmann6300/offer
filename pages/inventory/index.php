<?php
require_once __DIR__ . '/../../src/Auth.php';
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
        <?php if (Auth::hasPermission('manager')): ?>
        <div class="mt-4 md:mt-0 flex gap-2">
            <a href="sync.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                🔄 Synchronize from EasyVerein
            </a>
            <button type="button" onclick="document.getElementById('importModal').classList.remove('hidden')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-file-import mr-2"></i>
                Massenimport
            </button>
        </div>
        <?php endif; ?>
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
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-file-import text-green-600 mr-2"></i>
                Inventar Massenimport
            </h2>
            <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">JSON-Datei auswählen</label>
                <input 
                    type="file" 
                    name="json_file" 
                    accept=".json,application/json"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                <p class="mt-2 text-sm text-gray-500">
                    Laden Sie eine JSON-Datei mit Inventar-Artikeln hoch
                </p>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>JSON-Format
                </h3>
                <p class="text-sm text-blue-800 mb-2">Die JSON-Datei sollte ein Array von Objekten enthalten:</p>
                <pre class="text-xs bg-white p-3 rounded border border-blue-200 overflow-x-auto"><code>[
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
                <div class="mt-3 text-sm text-blue-800">
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
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
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
        <?php if (Auth::hasPermission('manager')): ?>
        <a href="sync.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-block mt-4">
            🔄 Synchronize from EasyVerein
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <?php foreach ($items as $item): ?>
    <div class="card overflow-hidden card-hover <?php echo $item['is_archived_in_easyverein'] ? 'opacity-60' : ''; ?>">
        <!-- Image -->
        <div class="h-48 bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center <?php echo $item['is_archived_in_easyverein'] ? 'grayscale' : ''; ?>">
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
                <!-- Sync Status Badge -->
                <?php if (!empty($item['easyverein_id'])): ?>
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full" title="Synchronized with EasyVerein">
                    <i class="fas fa-sync-alt mr-1"></i>Synced
                </span>
                <?php else: ?>
                <span class="px-2 py-1 text-xs bg-gray-200 text-gray-600 rounded-full" title="Local item only">
                    <i class="fas fa-desktop mr-1"></i>Local only
                </span>
                <?php endif; ?>
                <!-- Archived Badge -->
                <?php if ($item['is_archived_in_easyverein']): ?>
                <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded-full" title="Archived in EasyVerein">
                    <i class="fas fa-archive mr-1"></i>Archiviert
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
                <?php if (Auth::hasPermission('manager')): ?>
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
