<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/Invoice.php';
require_once __DIR__ . '/../../includes/models/User.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Access Control: Allow only 'board', 'alumni_board', 'head'
// Deny: 'member', 'candidate', 'alumni'
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = $user['role'] ?? '';

// Check if user has one of the allowed roles
$allowedRoles = ['admin', 'board', 'alumni_board', 'head'];
if (!in_array($userRole, $allowedRoles)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Get invoices based on role
$invoices = Invoice::getAll($userRole, $user['id']);

// Get statistics (only for board/alumni_board)
$stats = null;
if (in_array($userRole, ['admin', 'board', 'alumni_board'])) {
    $stats = Invoice::getStats();
}

// Get user database for fetching submitter info
$userDb = Database::getUserDB();

$title = 'Rechnungsmanagement - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php 
        unset($_SESSION['success_message']); 
    endif; 
    ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error_message']); ?>
    </div>
    <?php 
        unset($_SESSION['error_message']); 
    endif; 
    ?>

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-file-invoice-dollar mr-3 text-blue-600"></i>
                Rechnungsmanagement
            </h1>
            <p class="text-gray-600">Verwalten Sie Belege und Erstattungen</p>
        </div>
        
        <!-- New Submission Button -->
        <button 
            id="openSubmissionModal"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl"
        >
            <i class="fas fa-plus mr-2"></i>
            Neue Einreichung
        </button>
    </div>

    <!-- Stats Widgets (Board/Alumni Board Only) -->
    <?php if ($stats): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Offene Beträge -->
        <div class="card p-6 bg-gradient-to-br from-yellow-50 to-orange-50 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Offene Beträge</p>
                    <p class="text-3xl font-bold text-gray-800">
                        <?php echo number_format($stats['total_pending'], 2, ',', '.'); ?> €
                    </p>
                </div>
                <div class="w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-hourglass-half text-white text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Diesen Monat ausgezahlt -->
        <div class="card p-6 bg-gradient-to-br from-green-50 to-emerald-50 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Diesen Monat ausgezahlt</p>
                    <p class="text-3xl font-bold text-gray-800">
                        <?php echo number_format($stats['monthly_paid'], 2, ',', '.'); ?> €
                    </p>
                </div>
                <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Export Button (Board Only) -->
    <?php if (in_array($userRole, ['admin', 'board'])): ?>
    <div class="mb-6 flex justify-end">
        <a 
            href="<?php echo asset('api/export_invoices.php'); ?>"
            class="px-6 py-2 bg-gray-600 text-white rounded-lg font-semibold hover:bg-gray-700 transition-all shadow-md"
        >
            <i class="fas fa-download mr-2"></i>
            Alle Belege Exportieren
        </a>
    </div>
    <?php endif; ?>

    <!-- Invoices Table -->
    <div class="card overflow-hidden">
        <?php if (empty($invoices)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <p class="text-xl text-gray-600 mb-2">Keine Rechnungen vorhanden</p>
                <p class="text-gray-500">Erstellen Sie Ihre erste Einreichung</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Datum
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Einreicher
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Zweck
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Betrag
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Beleg
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <?php if ($userRole === 'board' || $userRole === 'admin'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Aktionen
                            </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($invoices as $invoice): ?>
                            <?php
                            // Get submitter info
                            $submitterStmt = $userDb->prepare("SELECT email FROM users WHERE id = ?");
                            $submitterStmt->execute([$invoice['user_id']]);
                            $submitter = $submitterStmt->fetch();
                            $submitterEmail = $submitter ? $submitter['email'] : 'Unknown';
                            
                            // Extract name from email (first part before @)
                            $submitterName = explode('@', $submitterEmail)[0];
                            
                            // Generate initials for avatar
                            $initials = strtoupper(substr($submitterName, 0, 2));
                            
                            // Status badge colors
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                'approved' => 'bg-green-100 text-green-800 border-green-300',
                                'rejected' => 'bg-red-100 text-red-800 border-red-300'
                            ];
                            
                            $statusLabels = [
                                'pending' => 'Ausstehend',
                                'approved' => 'Genehmigt',
                                'rejected' => 'Abgelehnt'
                            ];
                            
                            $statusClass = $statusColors[$invoice['status']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                            $statusLabel = $statusLabels[$invoice['status']] ?? ucfirst($invoice['status']);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d.m.Y', strtotime($invoice['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold mr-3">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </div>
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($submitterName); ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($invoice['description']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                    <?php echo number_format($invoice['amount'], 2, ',', '.'); ?> €
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($invoice['file_path'])): ?>
                                        <a href="<?php echo asset($invoice['file_path']); ?>" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-file-pdf mr-1"></i>
                                            Ansehen
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Kein Beleg</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full border <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($statusLabel); ?>
                                    </span>
                                </td>
                                <?php if ($userRole === 'board' || $userRole === 'admin'): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if ($invoice['status'] === 'pending'): ?>
                                        <div class="flex gap-2">
                                            <button 
                                                onclick="updateInvoiceStatus(<?php echo $invoice['id']; ?>, 'approved')"
                                                class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                                                title="Genehmigen"
                                            >
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button 
                                                onclick="updateInvoiceStatus(<?php echo $invoice['id']; ?>, 'rejected')"
                                                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                                                title="Ablehnen"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Submission Modal -->
<div id="submissionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                    Neue Rechnung einreichen
                </h2>
                <button id="closeSubmissionModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
        </div>
        
        <form id="submissionForm" action="<?php echo asset('api/submit_invoice.php'); ?>" method="POST" enctype="multipart/form-data" class="p-6">
            <!-- Betrag -->
            <div class="mb-6">
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Betrag (€) <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number" 
                    id="amount" 
                    name="amount" 
                    step="0.01"
                    min="0"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="0.00"
                >
            </div>

            <!-- Belegdatum -->
            <div class="mb-6">
                <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                    Belegdatum <span class="text-red-500">*</span>
                </label>
                <input 
                    type="date" 
                    id="date" 
                    name="date" 
                    required
                    max="<?php echo date('Y-m-d'); ?>"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            <!-- Zweck/Beschreibung -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Zweck <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Beschreiben Sie den Zweck der Rechnung..."
                ></textarea>
            </div>

            <!-- File Upload (Drag & Drop Zone) -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Beleg hochladen <span class="text-red-500">*</span>
                </label>
                <div 
                    id="dropZone"
                    class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors cursor-pointer"
                >
                    <input 
                        type="file" 
                        id="file" 
                        name="file" 
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                        class="hidden"
                    >
                    <div id="dropZoneContent">
                        <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 mb-2">
                            <span class="text-blue-600 font-semibold">Klicken Sie hier</span> oder ziehen Sie eine Datei hierher
                        </p>
                        <p class="text-sm text-gray-500">
                            Unterstützt: PDF, JPG, PNG (Max. 10MB)
                        </p>
                    </div>
                    <div id="fileInfo" class="hidden">
                        <i class="fas fa-file-check text-5xl text-green-500 mb-4"></i>
                        <p id="fileName" class="text-gray-700 font-semibold mb-2"></p>
                        <button 
                            type="button"
                            id="removeFile"
                            class="text-sm text-red-600 hover:text-red-800"
                        >
                            <i class="fas fa-times mr-1"></i>
                            Datei entfernen
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <button 
                    type="submit"
                    class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    Einreichen
                </button>
                <button 
                    type="button"
                    id="cancelSubmission"
                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all"
                >
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal controls
const modal = document.getElementById('submissionModal');
const openBtn = document.getElementById('openSubmissionModal');
const closeBtn = document.getElementById('closeSubmissionModal');
const cancelBtn = document.getElementById('cancelSubmission');

openBtn.addEventListener('click', () => {
    modal.classList.remove('hidden');
});

closeBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
});

cancelBtn.addEventListener('click', () => {
    modal.classList.add('hidden');
});

// Close modal when clicking outside
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.classList.add('hidden');
    }
});

// File upload handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('file');
const dropZoneContent = document.getElementById('dropZoneContent');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const removeFileBtn = document.getElementById('removeFile');

dropZone.addEventListener('click', () => {
    fileInput.click();
});

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    
    if (e.dataTransfer.files.length > 0) {
        fileInput.files = e.dataTransfer.files;
        updateFileInfo();
    }
});

fileInput.addEventListener('change', updateFileInfo);

removeFileBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    fileInput.value = '';
    dropZoneContent.classList.remove('hidden');
    fileInfo.classList.add('hidden');
});

function updateFileInfo() {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        fileName.textContent = file.name;
        dropZoneContent.classList.add('hidden');
        fileInfo.classList.remove('hidden');
    }
}

// Update invoice status function
function updateInvoiceStatus(invoiceId, status) {
    if (!confirm(`Möchten Sie diese Rechnung wirklich ${status === 'approved' ? 'genehmigen' : 'ablehnen'}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('invoice_id', invoiceId);
    formData.append('status', status);
    
    fetch('<?php echo asset('api/update_invoice_status.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Aktualisieren des Status');
    });
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
