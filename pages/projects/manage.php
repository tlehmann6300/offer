<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../includes/models/Project.php';
require_once __DIR__ . '/../../includes/utils/SecureImageUpload.php';
require_once __DIR__ . '/../../src/Database.php';

// Only board and manager can access
Auth::requireRole('manager');

$message = '';
$error = '';
$showForm = isset($_GET['new']) || isset($_GET['edit']);
$editProject = null;

// Handle POST request for creating/updating project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
    
    try {
        $projectData = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'client_name' => trim($_POST['client_name'] ?? ''),
            'client_contact_details' => trim($_POST['client_contact_details'] ?? ''),
            'priority' => $_POST['priority'] ?? 'medium',
            'status' => $_POST['status'] ?? 'draft',
            'max_consultants' => max(1, intval($_POST['max_consultants'] ?? 1)),
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
        ];
        
        // Validate required fields
        if (empty($projectData['title'])) {
            throw new Exception('Titel ist erforderlich');
        }
        
        // Handle image upload
        if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = SecureImageUpload::uploadImage($_FILES['project_image']);
            
            if (!$uploadResult['success']) {
                throw new Exception($uploadResult['error']);
            }
            
            $projectData['image_path'] = $uploadResult['path'];
        }
        
        $projectId = intval($_POST['project_id'] ?? 0);
        
        if ($projectId > 0) {
            // Update existing project
            // If no new image uploaded, keep the old one
            if (!isset($projectData['image_path'])) {
                unset($projectData['image_path']);
            }
            
            Project::update($projectId, $projectData);
            $message = 'Projekt erfolgreich aktualisiert';
        } else {
            // Create new project
            $projectId = Project::create($projectData);
            $message = 'Projekt erfolgreich erstellt';
        }
        
        // Redirect to manage page after successful save
        header('Location: manage.php?success=1&msg=' . urlencode($message));
        exit;
        
    } catch (Exception $e) {
        $error = 'Fehler beim Speichern: ' . $e->getMessage();
        $showForm = true;
    }
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
    
    $projectId = intval($_POST['project_id'] ?? 0);
    
    try {
        $db = Database::getContentDB();
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $message = 'Projekt erfolgreich gelöscht';
    } catch (Exception $e) {
        $error = 'Fehler beim Löschen: ' . $e->getMessage();
    }
}

// Get success message from redirect
if (isset($_GET['success']) && isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Get project for editing
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $editProject = Project::getById($editId);
    if (!$editProject) {
        $error = 'Projekt nicht gefunden';
        $showForm = false;
    }
}

// Get all projects with application counts
$db = Database::getContentDB();
$stmt = $db->query("
    SELECT 
        p.*,
        COUNT(pa.id) as application_count
    FROM projects p
    LEFT JOIN project_applications pa ON p.id = pa.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$projects = $stmt->fetchAll();

$title = 'Projekt-Verwaltung - IBC Intranet';
ob_start();
?>

<?php if (!$showForm): ?>
<!-- Project List View -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-briefcase text-purple-600 mr-2"></i>
                Projekt-Verwaltung
            </h1>
            <p class="text-gray-600"><?php echo count($projects); ?> Projekt(e) gefunden</p>
        </div>
        <a href="manage.php?new=1" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Neues Projekt
        </a>
    </div>
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

<!-- Projects Grid -->
<?php if (empty($projects)): ?>
<div class="card p-12 text-center">
    <i class="fas fa-briefcase text-gray-400 text-6xl mb-4"></i>
    <h3 class="text-xl font-semibold text-gray-600 mb-2">Keine Projekte gefunden</h3>
    <p class="text-gray-500 mb-6">Es wurden noch keine Projekte erstellt.</p>
    <a href="manage.php?new=1" class="btn-primary inline-block">
        <i class="fas fa-plus mr-2"></i>Erstes Projekt erstellen
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($projects as $project): ?>
    <div class="card p-6 hover:shadow-lg transition">
        <!-- Image -->
        <?php if (!empty($project['image_path'])): ?>
        <div class="mb-4 rounded-lg overflow-hidden">
            <img src="/<?php echo htmlspecialchars($project['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($project['title']); ?>"
                 class="w-full h-48 object-cover">
        </div>
        <?php endif; ?>
        
        <!-- Status and Priority Badges -->
        <div class="flex items-start justify-between mb-4">
            <span class="px-3 py-1 text-xs font-semibold rounded-full
                <?php 
                switch($project['status']) {
                    case 'draft': echo 'bg-gray-100 text-gray-800'; break;
                    case 'tender': echo 'bg-blue-100 text-blue-800'; break;
                    case 'applying': echo 'bg-yellow-100 text-yellow-800'; break;
                    case 'assigned': echo 'bg-green-100 text-green-800'; break;
                    case 'running': echo 'bg-purple-100 text-purple-800'; break;
                    case 'completed': echo 'bg-teal-100 text-teal-800'; break;
                    case 'archived': echo 'bg-red-100 text-red-800'; break;
                }
                ?>">
                <?php 
                switch($project['status']) {
                    case 'draft': echo 'Entwurf'; break;
                    case 'tender': echo 'Ausschreibung'; break;
                    case 'applying': echo 'Bewerbungsphase'; break;
                    case 'assigned': echo 'Vergeben'; break;
                    case 'running': echo 'Laufend'; break;
                    case 'completed': echo 'Abgeschlossen'; break;
                    case 'archived': echo 'Archiviert'; break;
                }
                ?>
            </span>
            <span class="px-2 py-1 text-xs font-semibold rounded-full
                <?php 
                switch($project['priority']) {
                    case 'low': echo 'bg-blue-100 text-blue-800'; break;
                    case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                    case 'high': echo 'bg-red-100 text-red-800'; break;
                }
                ?>">
                <?php 
                switch($project['priority']) {
                    case 'low': echo '<i class="fas fa-arrow-down"></i> Niedrig'; break;
                    case 'medium': echo '<i class="fas fa-minus"></i> Mittel'; break;
                    case 'high': echo '<i class="fas fa-arrow-up"></i> Hoch'; break;
                }
                ?>
            </span>
        </div>

        <!-- Title -->
        <h3 class="text-xl font-bold text-gray-800 mb-2">
            <?php echo htmlspecialchars($project['title']); ?>
        </h3>

        <!-- Description -->
        <?php if (!empty($project['description'])): ?>
        <p class="text-sm text-gray-600 mb-4 line-clamp-3">
            <?php echo htmlspecialchars(substr($project['description'], 0, 150)) . (strlen($project['description']) > 150 ? '...' : ''); ?>
        </p>
        <?php endif; ?>

        <!-- Project Info -->
        <div class="space-y-2 mb-4 text-sm text-gray-600">
            <?php if (!empty($project['client_name'])): ?>
            <div class="flex items-center">
                <i class="fas fa-user-tie w-5 text-purple-600"></i>
                <span><?php echo htmlspecialchars($project['client_name']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($project['start_date'])): ?>
            <div class="flex items-center">
                <i class="fas fa-calendar-start w-5 text-purple-600"></i>
                <span>Start: <?php echo date('d.m.Y', strtotime($project['start_date'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($project['end_date'])): ?>
            <div class="flex items-center">
                <i class="fas fa-calendar-check w-5 text-purple-600"></i>
                <span>Ende: <?php echo date('d.m.Y', strtotime($project['end_date'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($project['max_consultants'])): ?>
            <div class="flex items-center">
                <i class="fas fa-users-cog w-5 text-purple-600"></i>
                <span>Benötigt: <?php echo intval($project['max_consultants']); ?> Berater</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Application Count -->
        <div class="mb-4 p-3 bg-purple-50 rounded-lg">
            <a href="applications.php?project_id=<?php echo $project['id']; ?>" class="text-sm text-gray-700 hover:text-purple-600 transition">
                <i class="fas fa-users mr-1"></i>
                <strong><?php echo $project['application_count']; ?></strong> Bewerbung(en)
            </a>
        </div>

        <!-- Actions -->
        <div class="flex space-x-2">
            <a href="manage.php?edit=<?php echo $project['id']; ?>" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-center text-sm">
                <i class="fas fa-edit mr-1"></i>Bearbeiten
            </a>
            <button 
                class="delete-project-btn px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm"
                data-project-id="<?php echo $project['id']; ?>"
                data-project-name="<?php echo htmlspecialchars($project['title']); ?>"
                title="Löschen"
            >
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
            Projekt löschen
        </h3>
        <p class="text-gray-600 mb-6">
            Möchten Sie das Projekt "<span id="deleteProjectName" class="font-semibold"></span>" wirklich löschen? 
            Diese Aktion kann nicht rückgängig gemacht werden.
        </p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
            <input type="hidden" name="project_id" id="deleteProjectId" value="">
            <input type="hidden" name="delete_project" value="1">
            <div class="flex space-x-4">
                <button type="button" id="closeDeleteModalBtn" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Abbrechen
                </button>
                <button type="submit" class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i>Löschen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Delete button event listeners
document.querySelectorAll('.delete-project-btn').forEach(button => {
    button.addEventListener('click', function() {
        const projectId = this.getAttribute('data-project-id');
        const projectName = this.getAttribute('data-project-name');
        confirmDelete(projectId, projectName);
    });
});

function confirmDelete(projectId, projectName) {
    const deleteProjectId = document.getElementById('deleteProjectId');
    const deleteProjectName = document.getElementById('deleteProjectName');
    const deleteModal = document.getElementById('deleteModal');
    
    if (deleteProjectId) deleteProjectId.value = projectId;
    if (deleteProjectName) deleteProjectName.textContent = projectName;
    if (deleteModal) deleteModal.classList.remove('hidden');
}

function closeDeleteModal() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) deleteModal.classList.add('hidden');
}

// Close modal button
document.getElementById('closeDeleteModalBtn')?.addEventListener('click', closeDeleteModal);

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Close modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal') {
        closeDeleteModal();
    }
});
</script>

<?php else: ?>
<!-- Project Form View -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-3xl font-bold text-gray-800">
            <i class="fas fa-briefcase text-purple-600 mr-2"></i>
            <?php echo $editProject ? 'Projekt bearbeiten' : 'Neues Projekt'; ?>
        </h1>
        <a href="manage.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i>Zurück zur Übersicht
        </a>
    </div>
</div>

<?php if ($error): ?>
<div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- Project Form -->
<div class="card p-8">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
        <input type="hidden" name="save_project" value="1">
        <?php if ($editProject): ?>
        <input type="hidden" name="project_id" value="<?php echo $editProject['id']; ?>">
        <?php endif; ?>
        
        <!-- Title -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Titel <span class="text-red-500">*</span>
            </label>
            <input 
                type="text" 
                name="title" 
                value="<?php echo htmlspecialchars($editProject['title'] ?? ''); ?>"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="Projekt-Titel eingeben"
            >
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Beschreibung
            </label>
            <textarea 
                name="description" 
                rows="5"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="Projekt-Beschreibung eingeben"
            ><?php echo htmlspecialchars($editProject['description'] ?? ''); ?></textarea>
        </div>

        <!-- Client Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Kundenname
                </label>
                <input 
                    type="text" 
                    name="client_name" 
                    value="<?php echo htmlspecialchars($editProject['client_name'] ?? ''); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="Name des Kunden"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Kontaktdaten
                </label>
                <input 
                    type="text" 
                    name="client_contact_details" 
                    value="<?php echo htmlspecialchars($editProject['client_contact_details'] ?? ''); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="E-Mail, Telefon, etc."
                >
            </div>
        </div>

        <!-- Priority and Status -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Priorität
                </label>
                <select 
                    name="priority" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <option value="low" <?php echo (($editProject['priority'] ?? 'medium') === 'low') ? 'selected' : ''; ?>>Niedrig</option>
                    <option value="medium" <?php echo (($editProject['priority'] ?? 'medium') === 'medium') ? 'selected' : ''; ?>>Mittel</option>
                    <option value="high" <?php echo (($editProject['priority'] ?? 'medium') === 'high') ? 'selected' : ''; ?>>Hoch</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status
                </label>
                <select 
                    name="status" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
                    <option value="draft" <?php echo (($editProject['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Entwurf</option>
                    <option value="tender" <?php echo (($editProject['status'] ?? 'draft') === 'tender') ? 'selected' : ''; ?>>Ausschreibung</option>
                    <option value="applying" <?php echo (($editProject['status'] ?? 'draft') === 'applying') ? 'selected' : ''; ?>>Bewerbungsphase</option>
                    <option value="assigned" <?php echo (($editProject['status'] ?? 'draft') === 'assigned') ? 'selected' : ''; ?>>Vergeben</option>
                    <option value="running" <?php echo (($editProject['status'] ?? 'draft') === 'running') ? 'selected' : ''; ?>>Laufend</option>
                    <option value="completed" <?php echo (($editProject['status'] ?? 'draft') === 'completed') ? 'selected' : ''; ?>>Abgeschlossen</option>
                    <option value="archived" <?php echo (($editProject['status'] ?? 'draft') === 'archived') ? 'selected' : ''; ?>>Archiviert</option>
                </select>
            </div>
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Startdatum
                </label>
                <input 
                    type="date" 
                    name="start_date" 
                    value="<?php echo htmlspecialchars($editProject['start_date'] ?? ''); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Enddatum
                </label>
                <input 
                    type="date" 
                    name="end_date" 
                    value="<?php echo htmlspecialchars($editProject['end_date'] ?? ''); ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                >
            </div>
        </div>

        <!-- Required Consultants -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Benötigte Berater <span class="text-red-500">*</span>
            </label>
            <input 
                type="number" 
                name="max_consultants" 
                value="<?php echo htmlspecialchars($editProject['max_consultants'] ?? '1'); ?>"
                min="1"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="Anzahl benötigter Berater"
            >
        </div>

        <!-- Image Upload -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Projekt-Bild
            </label>
            <?php if ($editProject && !empty($editProject['image_path'])): ?>
            <div class="mb-4">
                <img src="/<?php echo htmlspecialchars($editProject['image_path']); ?>" 
                     alt="Aktuelles Bild"
                     class="w-64 h-48 object-cover rounded-lg border border-gray-300">
                <p class="text-sm text-gray-500 mt-2">Aktuelles Bild (wird ersetzt, wenn Sie ein neues hochladen)</p>
            </div>
            <?php endif; ?>
            <input 
                type="file" 
                name="project_image" 
                accept="image/jpeg,image/png,image/webp,image/gif"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
            >
            <p class="text-sm text-gray-500 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Erlaubte Formate: JPG, PNG, WebP, GIF. Maximale Größe: 5MB
            </p>
        </div>

        <!-- Form Actions -->
        <div class="flex space-x-4 pt-6 border-t border-gray-200">
            <a href="manage.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                Abbrechen
            </a>
            <button type="submit" class="flex-1 btn-primary">
                <i class="fas fa-save mr-2"></i>
                <?php echo $editProject ? 'Änderungen speichern' : 'Projekt erstellen'; ?>
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/templates/main_layout.php';
