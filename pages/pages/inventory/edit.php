<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';
require_once __DIR__ . '/../../includes/utils/SecureImageUpload.php';

if (!Auth::check() || !Auth::hasPermission('manager')) {
    header('Location: ../auth/login.php');
    exit;
}

$itemId = $_GET['id'] ?? null;
if (!$itemId) {
    header('Location: index.php');
    exit;
}

$item = Inventory::getById($itemId);
if (!$item) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
    
    if (isset($_POST['delete'])) {
        // Handle deletion
        if (Inventory::delete($itemId, $_SESSION['user_id'])) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Fehler beim Löschen des Artikels';
        }
    } else {
        // Handle update
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? null;
        $location_id = $_POST['location_id'] ?? null;
        $min_stock = intval($_POST['min_stock'] ?? 0);
        $unit = $_POST['unit'] ?? 'Stück';
        $unit_price = floatval($_POST['unit_price'] ?? 0);
        $notes = $_POST['notes'] ?? '';
        
        if (empty($name)) {
            $error = 'Name ist erforderlich';
        } else {
            $imagePath = $item['image_path'];
            
            // Handle image upload using secure upload utility
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = SecureImageUpload::uploadImage($_FILES['image']);
                
                if ($uploadResult['success']) {
                    // Delete old image if it exists
                    if ($imagePath) {
                        SecureImageUpload::deleteImage($imagePath);
                    }
                    $imagePath = $uploadResult['path'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            if (empty($error)) {
                $data = [
                    'name' => $name,
                    'description' => $description,
                    'category_id' => $category_id,
                    'location_id' => $location_id,
                    'min_stock' => $min_stock,
                    'unit' => $unit,
                    'unit_price' => $unit_price,
                    'image_path' => $imagePath,
                    'notes' => $notes
                ];
                
                try {
                    if (Inventory::update($itemId, $data, $_SESSION['user_id'])) {
                        header('Location: view.php?id=' . $itemId);
                        exit;
                    } else {
                        $error = 'Fehler beim Aktualisieren des Artikels';
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
        }
    }
}

$categories = Inventory::getCategories();
$locations = Inventory::getLocations();

$title = 'Artikel bearbeiten - Inventar';
ob_start();
?>

<div class="mb-6">
    <a href="view.php?id=<?php echo $item['id']; ?>" class="text-purple-600 hover:text-purple-700 inline-flex items-center mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Zurück zum Artikel
    </a>
</div>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="card p-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-edit text-purple-600 mr-2"></i>
            Artikel bearbeiten
        </h1>
        <?php if (Auth::hasPermission('admin')): ?>
        <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
            <i class="fas fa-trash mr-2"></i>Löschen
        </button>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                <input 
                    type="text" 
                    name="name" 
                    required 
                    value="<?php echo htmlspecialchars($item['name']); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategorie</label>
                <select 
                    name="category_id" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <option value="">Keine Kategorie</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                    <option value="">Kein Standort</option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?php echo $location['id']; ?>" <?php echo ($item['location_id'] == $location['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Beschreibung</label>
                <textarea 
                    name="description" 
                    rows="4"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                ><?php echo htmlspecialchars($item['description']); ?></textarea>
            </div>
        </div>

        <!-- Stock Info -->
        <div class="border-t pt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Bestandsinformationen</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aktueller Bestand</label>
                    <input 
                        type="number" 
                        value="<?php echo $item['current_stock']; ?>"
                        disabled
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100"
                    >
                    <p class="text-xs text-gray-500 mt-1">Verwenden Sie die Bestandsanpassung auf der Detailseite</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mindestbestand</label>
                    <input 
                        type="number" 
                        name="min_stock" 
                        min="0"
                        value="<?php echo $item['min_stock']; ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Einheit</label>
                    <input 
                        type="text" 
                        name="unit" 
                        value="<?php echo htmlspecialchars($item['unit']); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stückpreis (€)</label>
                    <input 
                        type="number" 
                        name="unit_price" 
                        min="0" 
                        step="0.01"
                        value="<?php echo $item['unit_price']; ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>
            </div>
        </div>

        <!-- Image Upload -->
        <div class="border-t pt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Bild</h2>
            <?php if ($item['image_path']): ?>
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Aktuelles Bild:</p>
                <img src="/<?php echo htmlspecialchars($item['image_path']); ?>" alt="Current" class="w-48 h-48 object-cover rounded-lg">
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Neues Bild hochladen (optional)</label>
                <input 
                    type="file" 
                    name="image" 
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                <p class="text-sm text-gray-500 mt-2">Erlaubt: JPG, PNG, GIF, WebP. Maximum: 5MB</p>
            </div>
        </div>

        <!-- Notes -->
        <div class="border-t pt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notizen</label>
            <textarea 
                name="notes" 
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            ><?php echo htmlspecialchars($item['notes']); ?></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-6 border-t">
            <a href="view.php?id=<?php echo $item['id']; ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Abbrechen
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Änderungen speichern
            </button>
        </div>
    </form>
</div>

<!-- Delete confirmation form (hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
    <input type="hidden" name="delete" value="1">
</form>

<script>
function confirmDelete() {
    if (confirm('Sind Sie sicher, dass Sie diesen Artikel löschen möchten? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
