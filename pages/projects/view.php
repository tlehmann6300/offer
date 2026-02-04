<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/Project.php';
require_once __DIR__ . '/../../includes/handlers/CSRFHandler.php';
require_once __DIR__ . '/../../src/Database.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = $_SESSION['user_role'] ?? 'member';

// Get project ID from query parameter
$projectId = intval($_GET['id'] ?? 0);
if ($projectId <= 0) {
    header('Location: index.php');
    exit;
}

// Get project details
$project = Project::getById($projectId);
if (!$project) {
    header('Location: index.php');
    exit;
}

// Filter sensitive data based on user role
$project = Project::filterSensitiveData($project, $userRole, $user['id']);

// Check if user has already applied
$userApplication = null;
if ($userRole !== 'alumni') {
    $userApplication = Project::getUserApplication($projectId, $user['id']);
}

// Handle application submission
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    CSRFHandler::verifyToken($_POST['csrf_token'] ?? '');
    
    // Check if user can apply
    if ($userRole === 'alumni') {
        $error = 'Alumni können sich nicht auf Projekte bewerben';
    } elseif ($project['status'] !== 'tender' && $project['status'] !== 'applying') {
        $error = 'Bewerbungen für dieses Projekt sind nicht möglich';
    } else {
        try {
            // Validate motivation
            $motivation = trim($_POST['motivation'] ?? '');
            if (empty($motivation)) {
                throw new Exception('Bitte geben Sie Ihre Motivation an');
            }
            
            // Validate experience count confirmation checkbox
            if (!isset($_POST['experience_confirmed']) || $_POST['experience_confirmed'] !== '1') {
                throw new Exception('Bitte bestätigen Sie, dass Sie die Anzahl bisheriger Projekte korrekt angegeben haben');
            }
            
            // Validate GDPR consent checkbox
            if (!isset($_POST['gdpr_consent']) || $_POST['gdpr_consent'] !== '1') {
                throw new Exception('Sie müssen der Datenverarbeitung gemäß DSGVO zustimmen');
            }
            
            $applicationData = [
                'motivation' => $motivation,
                'experience_count' => intval($_POST['experience_count'] ?? 0)
            ];
            
            Project::apply($projectId, $user['id'], $applicationData);
            $message = 'Ihre Bewerbung wurde erfolgreich eingereicht';
            
            // Reload application status
            $userApplication = Project::getUserApplication($projectId, $user['id']);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$title = htmlspecialchars($project['title']) . ' - IBC Intranet';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center text-purple-600 hover:text-purple-700 transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Zurück zur Übersicht
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

    <!-- Project Card -->
    <div class="card p-8">
        <!-- Image -->
        <?php if (!empty($project['image_path'])): ?>
        <div class="mb-6 rounded-lg overflow-hidden">
            <img src="/<?php echo htmlspecialchars($project['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($project['title']); ?>"
                 class="w-full h-96 object-cover">
        </div>
        <?php endif; ?>
        
        <!-- Status and Priority -->
        <div class="flex items-center gap-3 mb-6">
            <span class="px-4 py-2 text-sm font-semibold rounded-full
                <?php 
                switch($project['status']) {
                    case 'tender': echo 'bg-blue-100 text-blue-800'; break;
                    case 'applying': echo 'bg-yellow-100 text-yellow-800'; break;
                    case 'assigned': echo 'bg-green-100 text-green-800'; break;
                    case 'running': echo 'bg-purple-100 text-purple-800'; break;
                    case 'completed': echo 'bg-teal-100 text-teal-800'; break;
                    case 'archived': echo 'bg-gray-200 text-gray-600'; break;
                    default: echo 'bg-gray-100 text-gray-800'; break;
                }
                ?>">
                <?php 
                switch($project['status']) {
                    case 'tender': echo 'Ausschreibung'; break;
                    case 'applying': echo 'Bewerbungsphase'; break;
                    case 'assigned': echo 'Vergeben'; break;
                    case 'running': echo 'Laufend'; break;
                    case 'completed': echo 'Abgeschlossen'; break;
                    case 'archived': echo 'Archiviert'; break;
                    default: echo ucfirst($project['status']); break;
                }
                ?>
            </span>
            
            <span class="px-3 py-2 text-sm font-semibold rounded-full
                <?php 
                switch($project['priority']) {
                    case 'low': echo 'bg-blue-100 text-blue-800'; break;
                    case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                    case 'high': echo 'bg-red-100 text-red-800'; break;
                    default: echo 'bg-gray-100 text-gray-800'; break;
                }
                ?>">
                <?php 
                switch($project['priority']) {
                    case 'low': echo '<i class="fas fa-arrow-down"></i> Niedrig'; break;
                    case 'medium': echo '<i class="fas fa-minus"></i> Mittel'; break;
                    case 'high': echo '<i class="fas fa-arrow-up"></i> Hoch'; break;
                    default: echo ucfirst($project['priority']); break;
                }
                ?>
            </span>
        </div>
        
        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6">
            <?php echo htmlspecialchars($project['title']); ?>
        </h1>
        
        <!-- Project Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
            <?php if (!empty($project['client_name'])): ?>
            <div class="flex items-center">
                <i class="fas fa-user-tie w-6 text-purple-600 mr-2"></i>
                <div>
                    <div class="text-xs text-gray-500">Kunde</div>
                    <div class="font-semibold"><?php echo htmlspecialchars($project['client_name']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($project['start_date'])): ?>
            <div class="flex items-center">
                <i class="fas fa-calendar-start w-6 text-purple-600 mr-2"></i>
                <div>
                    <div class="text-xs text-gray-500">Startdatum</div>
                    <div class="font-semibold"><?php echo date('d.m.Y', strtotime($project['start_date'])); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($project['end_date'])): ?>
            <div class="flex items-center">
                <i class="fas fa-calendar-check w-6 text-purple-600 mr-2"></i>
                <div>
                    <div class="text-xs text-gray-500">Enddatum</div>
                    <div class="font-semibold"><?php echo date('d.m.Y', strtotime($project['end_date'])); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Description -->
        <?php if (!empty($project['description'])): ?>
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-3">Beschreibung</h2>
            <div class="text-gray-700 leading-relaxed whitespace-pre-line">
                <?php echo htmlspecialchars($project['description']); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Application Section (Only for members, when status is 'applying' or 'tender') -->
        <?php if (($project['status'] === 'tender' || $project['status'] === 'applying') && $userRole !== 'alumni'): ?>
        <div class="border-t border-gray-200 pt-6 mt-6">
            <?php if ($userApplication): ?>
                <!-- Show Application Status -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                        Ihre Bewerbung
                    </h2>
                    
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="ml-2 px-3 py-1 text-sm font-semibold rounded-full
                                <?php 
                                switch($userApplication['status']) {
                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'reviewing': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                    case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800'; break;
                                }
                                ?>">
                                <?php 
                                switch($userApplication['status']) {
                                    case 'pending': echo 'In Prüfung'; break;
                                    case 'reviewing': echo 'Wird geprüft'; break;
                                    case 'accepted': echo 'Akzeptiert'; break;
                                    case 'rejected': echo 'Abgelehnt'; break;
                                    default: echo ucfirst($userApplication['status']); break;
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div>
                            <span class="text-sm text-gray-600">Bewerbungsdatum:</span>
                            <span class="ml-2 font-semibold"><?php echo date('d.m.Y H:i', strtotime($userApplication['created_at'])); ?> Uhr</span>
                        </div>
                        
                        <?php if (!empty($userApplication['motivation'])): ?>
                        <div class="mt-4">
                            <span class="text-sm font-semibold text-gray-700">Ihre Motivation:</span>
                            <div class="mt-2 p-3 bg-white rounded border border-gray-200 text-gray-700 whitespace-pre-line">
                                <?php echo htmlspecialchars($userApplication['motivation']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['action']) && $_GET['action'] === 'apply'): ?>
                <!-- Show Application Form -->
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-paper-plane text-blue-600 mr-2" aria-hidden="true"></i>
                        Jetzt bewerben
                    </h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo CSRFHandler::getToken(); ?>">
                        <input type="hidden" name="apply" value="1">
                        
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-comment-dots text-blue-600 mr-2" aria-hidden="true"></i>
                                Motivation <span class="text-red-500 ml-1">*</span>
                            </label>
                            <textarea 
                                name="motivation" 
                                rows="5"
                                required
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                placeholder="Warum möchten Sie an diesem Projekt teilnehmen?"
                            ></textarea>
                        </div>
                        
                        <div>
                            <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-briefcase text-blue-600 mr-2" aria-hidden="true"></i>
                                Anzahl bisheriger Projekterfahrungen
                            </label>
                            <input 
                                type="number" 
                                name="experience_count" 
                                min="0"
                                value="0"
                                class="w-full p-3 border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            >
                        </div>
                        
                        <!-- Experience Confirmation Checkbox -->
                        <div class="flex items-start">
                            <input 
                                type="checkbox" 
                                id="experience_confirmed" 
                                name="experience_confirmed" 
                                value="1"
                                required
                                class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <label for="experience_confirmed" class="ml-3 text-sm text-gray-700">
                                Ich bestätige, dass ich die Anzahl bisheriger Projekte korrekt angegeben habe <span class="text-red-500">*</span>
                            </label>
                        </div>
                        
                        <!-- GDPR Consent Checkbox -->
                        <div class="flex items-start">
                            <input 
                                type="checkbox" 
                                id="gdpr_consent" 
                                name="gdpr_consent" 
                                value="1"
                                required
                                class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <label for="gdpr_consent" class="ml-3 text-sm text-gray-700">
                                Ich willige in die Verarbeitung meiner Daten zwecks Projektvergabe ein (DSGVO) <span class="text-red-500">*</span>
                            </label>
                        </div>
                        
                        <div class="flex space-x-4 pt-4">
                            <a href="view.php?id=<?php echo $project['id']; ?>" 
                               class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                                Abbrechen
                            </a>
                            <button type="submit" 
                                    class="flex-1 px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-bold text-lg hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 transition shadow-lg hover:shadow-xl">
                                <i class="fas fa-paper-plane mr-2" aria-hidden="true"></i>
                                Bewerbung absenden
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Show "Apply Now" button when user hasn't applied yet -->
                <a href="view.php?id=<?php echo $project['id']; ?>&action=apply" 
                   class="inline-block px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Jetzt bewerben
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
