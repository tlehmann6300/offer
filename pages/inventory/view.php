<?php
require_once __DIR__ . '/../../includes/handlers/AuthHandler.php';
require_once __DIR__ . '/../../includes/models/Inventory.php';

AuthHandler::startSession();

if (!AuthHandler::isAuthenticated()) {
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

// Check for success messages from checkout
if (isset($_SESSION['checkout_success'])) {
    $message = $_SESSION['checkout_success'];
    unset($_SESSION['checkout_success']);
}

// Check for rental messages
if (isset($_SESSION['rental_success'])) {
    $message = $_SESSION['rental_success'];
    unset($_SESSION['rental_success']);
}

if (isset($_SESSION['rental_error'])) {
    $error = $_SESSION['rental_error'];
    unset($_SESSION['rental_error']);
}

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    if (!AuthHandler::hasPermission('manager')) {
        $error = 'Keine Berechtigung';
    } else {
        $amount = intval($_POST['amount'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        $comment = $_POST['comment'] ?? '';
        
        if (empty($reason) || empty($comment)) {
            $error = 'Grund und Kommentar sind erforderlich';
        } else {
            if (Inventory::adjustStock($itemId, $amount, $reason, $comment, $_SESSION['user_id'])) {
                $message = 'Bestand erfolgreich aktualisiert';
                $item = Inventory::getById($itemId); // Reload item
            } else {
                $error = 'Fehler beim Aktualisieren des Bestands';
            }
        }
    }
}

$history = Inventory::getHistory($itemId, 20);
$activeCheckouts = Inventory::getItemCheckouts($itemId);

$title = htmlspecialchars($item['name']) . ' - Inventar';
ob_start();
?>

<div class="mb-6">
    <a href="index.php" class="text-purple-600 hover:text-purple-700 inline-flex items-center mb-4">
        <i class="fas fa-arrow-left mr-2"></i>Zurück zum Inventar
    </a>
</div>

<?php if ($message): ?>
<div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Item Details -->
    <div class="lg:col-span-2">
        <div class="card p-6">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($item['name']); ?></h1>
                    <div class="flex flex-wrap gap-2">
                        <?php if ($item['category_name']): ?>
                        <span class="px-3 py-1 text-sm rounded-full" style="background-color: <?php echo htmlspecialchars($item['category_color']); ?>20; color: <?php echo htmlspecialchars($item['category_color']); ?>">
                            <?php echo htmlspecialchars($item['category_name']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($item['location_name']): ?>
                        <span class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full">
                            <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($item['location_name']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (AuthHandler::hasPermission('manager')): ?>
                <div class="flex space-x-2">
                    <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn-primary">
                        <i class="fas fa-edit mr-2"></i>Bearbeiten
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Checkout/Borrow Button for all users -->
            <?php if ($item['current_stock'] > 0): ?>
            <div class="mb-6 flex gap-3">
                <a href="checkout.php?id=<?php echo $item['id']; ?>" class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold">
                    <i class="fas fa-hand-holding-box mr-2"></i>Entnehmen / Ausleihen
                </a>
                <button onclick="openRentalModal()" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-semibold">
                    <i class="fas fa-calendar-alt mr-2"></i>Ausleihen (mit Rückgabedatum)
                </button>
            </div>
            <?php endif; ?>

            <!-- Image -->
            <?php if ($item['image_path']): ?>
            <div class="mb-6">
                <img src="/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-full max-w-lg rounded-lg shadow-md">
            </div>
            <?php endif; ?>

            <!-- Description -->
            <?php if ($item['description']): ?>
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-2">Beschreibung</h2>
                <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Aktueller Bestand</p>
                    <p class="text-2xl font-bold <?php echo $item['current_stock'] <= $item['min_stock'] && $item['min_stock'] > 0 ? 'text-red-600' : 'text-gray-800'; ?>">
                        <?php echo $item['current_stock']; ?>
                    </p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['unit']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Mindestbestand</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $item['min_stock']; ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['unit']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Stückpreis</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($item['unit_price'], 2); ?> €</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-1">Gesamtwert</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($item['current_stock'] * $item['unit_price'], 2); ?> €</p>
                </div>
            </div>

            <!-- Notes -->
            <?php if ($item['notes']): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-gray-700"><strong>Notizen:</strong> <?php echo nl2br(htmlspecialchars($item['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stock Adjustment -->
    <div class="lg:col-span-1">
        <?php if (AuthHandler::hasPermission('manager')): ?>
        <div class="card p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-exchange-alt text-purple-600 mr-2"></i>
                Bestand anpassen
            </h2>
            <form method="POST">
                <input type="hidden" name="adjust_stock" value="1">
                
                <!-- Quick Buttons -->
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <button type="button" onclick="setAmount(-10)" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                        <i class="fas fa-minus mr-1"></i>10
                    </button>
                    <button type="button" onclick="setAmount(-1)" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition">
                        <i class="fas fa-minus mr-1"></i>1
                    </button>
                    <button type="button" onclick="setAmount(1)" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition">
                        <i class="fas fa-plus mr-1"></i>1
                    </button>
                    <button type="button" onclick="setAmount(10)" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition">
                        <i class="fas fa-plus mr-1"></i>10
                    </button>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Menge</label>
                    <input 
                        type="number" 
                        id="amount" 
                        name="amount" 
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="z.B. +5 oder -3"
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grund *</label>
                    <select 
                        name="reason" 
                        required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    >
                        <option value="">Bitte wählen...</option>
                        <option value="Verliehen">Verliehen</option>
                        <option value="Zurückgegeben">Zurückgegeben</option>
                        <option value="Gekauft">Gekauft</option>
                        <option value="Verkauft">Verkauft</option>
                        <option value="Beschädigt">Beschädigt</option>
                        <option value="Verloren">Verloren</option>
                        <option value="Inventur">Inventur</option>
                        <option value="Sonstiges">Sonstiges</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kommentar *</label>
                    <textarea 
                        name="comment" 
                        required 
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Bitte beschreiben Sie die Änderung..."
                    ></textarea>
                </div>

                <button type="submit" class="w-full btn-primary">
                    <i class="fas fa-check mr-2"></i>Bestätigen
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Quick Info -->
        <div class="card p-6">
            <h3 class="font-semibold text-gray-800 mb-3">Information</h3>
            <div class="space-y-2 text-sm">
                <p class="text-gray-600">
                    <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                    Erstellt: <?php echo date('d.m.Y', strtotime($item['created_at'])); ?>
                </p>
                <p class="text-gray-600">
                    <i class="fas fa-clock text-gray-400 mr-2"></i>
                    Aktualisiert: <?php echo date('d.m.Y', strtotime($item['updated_at'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Active Checkouts -->
<?php if (!empty($activeCheckouts)): ?>
<div class="card p-6 mt-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-clipboard-list text-blue-600 mr-2"></i>
        Aktive Ausleihen
    </h2>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Benutzer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Menge</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verwendungszweck</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zielort</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($activeCheckouts as $checkout): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($checkout['user_email'] ?? 'User ID: ' . $checkout['user_id']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <span class="font-semibold text-gray-800"><?php echo $checkout['quantity']; ?></span>
                        <span class="text-gray-500"><?php echo htmlspecialchars($item['unit']); ?></span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($checkout['purpose']); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo $checkout['destination'] ? htmlspecialchars($checkout['destination']) : '-'; ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo date('d.m.Y', strtotime($checkout['checkout_date'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- History -->
<div class="card p-6 mt-6">
    <h2 class="text-xl font-bold text-gray-800 mb-4">
        <i class="fas fa-history text-blue-600 mr-2"></i>
        Verlauf
    </h2>
    <?php if (empty($history)): ?>
    <p class="text-gray-500 text-center py-4">Keine Verlaufsdaten vorhanden</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Änderung</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grund</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kommentar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($history as $entry): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo date('d.m.Y H:i', strtotime($entry['timestamp'])); ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php
                        $typeColors = [
                            'adjustment' => 'blue',
                            'create' => 'green',
                            'update' => 'yellow',
                            'delete' => 'red',
                            'checkout' => 'purple',
                            'checkin' => 'green',
                            'writeoff' => 'red'
                        ];
                        $color = $typeColors[$entry['change_type']] ?? 'gray';
                        $typeLabels = [
                            'adjustment' => 'Anpassung',
                            'create' => 'Erstellt',
                            'update' => 'Aktualisiert',
                            'delete' => 'Gelöscht',
                            'checkout' => 'Ausgeliehen',
                            'checkin' => 'Zurückgegeben',
                            'writeoff' => 'Ausschuss'
                        ];
                        $label = $typeLabels[$entry['change_type']] ?? $entry['change_type'];
                        ?>
                        <span class="px-2 py-1 text-xs bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-700 rounded-full">
                            <?php echo $label; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <?php if ($entry['change_type'] === 'adjustment'): ?>
                        <span class="font-semibold <?php echo $entry['change_amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo ($entry['change_amount'] >= 0 ? '+' : '') . $entry['change_amount']; ?>
                        </span>
                        <span class="text-gray-500 text-xs ml-2">
                            (<?php echo $entry['old_stock']; ?> → <?php echo $entry['new_stock']; ?>)
                        </span>
                        <?php else: ?>
                        <span class="text-gray-500">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($entry['reason'] ?? '-'); ?>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        <?php echo htmlspecialchars($entry['comment'] ?? '-'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Rental Modal -->
<div id="rentalModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-calendar-check text-purple-600 mr-2"></i>
                Artikel ausleihen
            </h2>
            <button onclick="closeRentalModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form method="POST" action="rental.php" class="space-y-4">
            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
            <input type="hidden" name="create_rental" value="1">

            <div class="bg-gray-50 p-3 rounded-lg mb-4">
                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($item['name']); ?></p>
                <p class="text-sm text-gray-600">Verfügbar: <?php echo $item['current_stock']; ?> <?php echo htmlspecialchars($item['unit']); ?></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Anzahl <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number" 
                    name="amount" 
                    required 
                    min="1" 
                    max="<?php echo $item['current_stock']; ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Anzahl eingeben"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Voraussichtliches Rückgabedatum <span class="text-red-500">*</span>
                </label>
                <input 
                    type="date" 
                    name="expected_return" 
                    required
                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Verwendungszweck <span class="text-red-500">*</span>
                </label>
                <textarea 
                    name="purpose" 
                    required 
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Wofür benötigen Sie diesen Artikel?"
                ></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeRentalModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Abbrechen
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    <i class="fas fa-check mr-2"></i>Ausleihen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function setAmount(value) {
    document.getElementById('amount').value = value;
}

function openRentalModal() {
    document.getElementById('rentalModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeRentalModal() {
    document.getElementById('rentalModal').classList.add('hidden');
    document.body.style.overflow = 'auto'; // Re-enable scrolling
}

// Close modal when clicking outside
document.getElementById('rentalModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRentalModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRentalModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
