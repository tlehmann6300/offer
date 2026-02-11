<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/Project.php';
require_once __DIR__ . '/../../src/Database.php';

// Check authentication - any logged-in user can view projects
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = $_SESSION['user_role'] ?? 'member';

// Get filter parameter from URL
$typeFilter = $_GET['type'] ?? 'all';
$validTypes = ['all', 'internal', 'external'];
if (!in_array($typeFilter, $validTypes)) {
    $typeFilter = 'all';
}

// Get all projects that are NOT draft
$db = Database::getContentDB();

if ($typeFilter === 'all') {
    $stmt = $db->query("
        SELECT * FROM projects 
        WHERE status != 'draft'
        ORDER BY created_at DESC
    ");
} else {
    $stmt = $db->prepare("
        SELECT * FROM projects 
        WHERE status != 'draft' AND type = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$typeFilter]);
}

$projects = $stmt->fetchAll();

// Filter sensitive data for each project based on user role
$filteredProjects = array_map(function($project) use ($userRole, $user) {
    return Project::filterSensitiveData($project, $userRole, $user['id']);
}, $projects);

$title = 'Projekte - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Error/Success Messages -->
    <?php if (isset($_SESSION['error'])): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-briefcase mr-3 text-purple-600"></i>
                Projekte
            </h1>
            <p class="text-gray-600 dark:text-gray-300">Entdecke aktuelle Projekte und bewirb Dich</p>
        </div>
        
        <!-- Neues Projekt Button - Board/Head/Manager only -->
        <?php if (Auth::hasPermission('manage_projects') || Auth::isBoard() || Auth::hasRole(['head', 'alumni_board'])): ?>
        <a href="manage.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg font-semibold hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl">
            <i class="fas fa-plus mr-2"></i>
            Neues Projekt
        </a>
        <?php endif; ?>
    </div>

    <!-- Filter Bar -->
    <div class="mb-6 card p-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="text-sm font-medium text-gray-700">Filter:</span>
            <a href="index.php?type=all" 
               class="px-4 py-2 rounded-lg font-semibold transition <?php echo $typeFilter === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>">
                <i class="fas fa-list mr-2"></i>Alle
            </a>
            <a href="index.php?type=internal" 
               class="px-4 py-2 rounded-lg font-semibold transition <?php echo $typeFilter === 'internal' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200'; ?>">
                <i class="fas fa-building mr-2"></i>Intern
            </a>
            <a href="index.php?type=external" 
               class="px-4 py-2 rounded-lg font-semibold transition <?php echo $typeFilter === 'external' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200'; ?>">
                <i class="fas fa-users mr-2"></i>Extern
            </a>
        </div>
    </div>

    <!-- Projects Grid -->
    <?php if (empty($filteredProjects)): ?>
        <div class="card p-8 text-center">
            <i class="fas fa-briefcase text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-600">Keine Projekte gefunden</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($filteredProjects as $project): ?>
                <?php
                    $isArchived = $project['status'] === 'archived';
                    $canApply = ($project['status'] === 'open' || $project['status'] === 'applying') && $userRole !== 'alumni';
                ?>
                
                <div class="card p-6 relative hover:shadow-lg transition <?php echo $isArchived ? 'opacity-60 grayscale' : ''; ?>">
                    <?php if ($isArchived): ?>
                        <div class="absolute top-4 right-4">
                            <span class="px-3 py-1 bg-gray-200 text-gray-600 text-xs font-semibold rounded-full">
                                <i class="fas fa-archive mr-1"></i>
                                Archiviert
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Image -->
                    <?php if (!empty($project['image_path'])): ?>
                        <a href="view.php?id=<?php echo $project['id']; ?>" class="block mb-4 rounded-lg overflow-hidden">
                            <img src="/<?php echo htmlspecialchars($project['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($project['title']); ?>"
                                 class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300">
                        </a>
                    <?php endif; ?>
                    
                    <!-- Status and Priority Badges -->
                    <div class="flex items-start justify-between mb-4 gap-2">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            <?php 
                            switch($project['status']) {
                                case 'open': echo 'bg-blue-100 text-blue-800'; break;
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
                                case 'open': echo 'Offen'; break;
                                case 'applying': echo 'Bewerbungsphase'; break;
                                case 'assigned': echo 'Vergeben'; break;
                                case 'running': echo 'Laufend'; break;
                                case 'completed': echo 'Abgeschlossen'; break;
                                case 'archived': echo 'Archiviert'; break;
                                default: echo ucfirst($project['status']); break;
                            }
                            ?>
                        </span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
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

                    <!-- Project Type Badge -->
                    <div class="mb-4">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            <?php 
                            $projectType = $project['type'] ?? 'internal';
                            echo $projectType === 'internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800';
                            ?>">
                            <i class="fas fa-tag mr-1"></i>
                            <?php echo $projectType === 'internal' ? 'Intern' : 'Extern'; ?>
                        </span>
                    </div>

                    <!-- Title -->
                    <a href="view.php?id=<?php echo $project['id']; ?>" class="block mb-2 hover:text-purple-600 transition">
                        <h3 class="text-xl font-bold text-gray-800">
                            <?php echo htmlspecialchars($project['title']); ?>
                        </h3>
                    </a>

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
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col space-y-2">
                        <a href="view.php?id=<?php echo $project['id']; ?>" 
                           class="block w-full text-center px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition">
                            <i class="fas fa-eye mr-2"></i>
                            Details ansehen
                        </a>
                        
                        <?php if ($canApply): ?>
                            <a href="view.php?id=<?php echo $project['id']; ?>&action=apply" 
                               class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Jetzt bewerben
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .grayscale {
        filter: grayscale(80%);
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
