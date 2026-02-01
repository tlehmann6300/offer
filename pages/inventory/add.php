<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';

AuthHandler::startSession();

if (!AuthHandler::isAuthenticated() || !AuthHandler::hasPermission('manager')) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $location_id = $_POST['location_id'] ?? null;
    $current_stock = intval($_POST['current_stock'] ?? 0);
    $min_stock = intval($_POST['min_stock'] ?? 0);
    $unit = $_POST['unit'] ?? 'Stück';
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    
    if (empty($name)) {
        $error = 'Name ist erforderlich';
    } else {
        $imagePath = null;
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ALLOWED_IMAGE_TYPES;
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = 'Ungültiger Dateityp. Nur JPG, PNG, GIF und WebP sind erlaubt.';
            } else if ($fileSize > UPLOAD_MAX_SIZE) {
                $error = 'Datei ist zu groß. Maximum: 5MB';
            } else {
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('item_') . '.' . $extension;
                $uploadPath = __DIR__ . '/../../assets/uploads/' . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $imagePath = 'assets/uploads/' . $filename;
                } else {
                    $error = 'Fehler beim Hochladen der Datei';
                }
            }
        }
        
        if (empty($error)) {
            $data = [
                'name' => $name,
                'description' => $description,
                'category_id' => $category_id,
                'location_id' => $location_id,
                'current_stock' => $current_stock,
                'min_stock' => $min_stock,
                'unit' => $unit,
                'unit_price' => $unit_price,
                'image_path' => $imagePath,
                'notes' => $notes
            ];
            
            $itemId = Inventory::create($data, $_SESSION['user_id']);
            
            if ($itemId) {
                header('Location: view.php?id=' . $itemId);
                exit;
            } else {
                $error = 'Fehler beim Erstellen des Artikels';
            }
        }
    }
}

$categories = Inventory::getCategories();
$locations = Inventory::getLocations();

$title = 'Neuer Artikel - Inventar';
ob_start();
?>

<div class="mb-6">
    <a href="index.php" class="text-purple-600 hover:text-purple-700 inline-flex items-center mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Zurück zum Inventar
    </a>
</div>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="card p-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">
        <i class="fas fa-plus text-purple-600 mr-2"></i>
        Neuer Artikel
    </h1>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                <input 
                    type="text" 
                    name="name" 
                    required 
                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Artikelname"
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
                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
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
                    <option value="<?php echo $location['id']; ?>" <?php echo (isset($_POST['location_id']) && $_POST['location_id'] == $location['id']) ? 'selected' : ''; ?>>
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
                    placeholder="Beschreibung des Artikels..."
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
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
                        name="current_stock" 
                        min="0"
                        value="<?php echo htmlspecialchars($_POST['current_stock'] ?? '0'); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mindestbestand</label>
                    <input 
                        type="number" 
                        name="min_stock" 
                        min="0"
                        value="<?php echo htmlspecialchars($_POST['min_stock'] ?? '0'); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Einheit</label>
                    <input 
                        type="text" 
                        name="unit" 
                        value="<?php echo htmlspecialchars($_POST['unit'] ?? 'Stück'); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="z.B. Stück, Karton, Liter"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stückpreis (€)</label>
                    <input 
                        type="number" 
                        name="unit_price" 
                        min="0" 
                        step="0.01"
                        value="<?php echo htmlspecialchars($_POST['unit_price'] ?? '0'); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                </div>
            </div>
        </div>

        <!-- Image Upload -->
        <div class="border-t pt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Bild</h2>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Artikelbild hochladen</label>
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
                placeholder="Zusätzliche Notizen..."
            ><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-6 border-t">
            <a href="index.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Abbrechen
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Artikel erstellen
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
