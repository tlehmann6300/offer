<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();

// Check if user has required role (board, head, member, or candidate)
$allowedRoles = ['board', 'head', 'member', 'candidate'];
if (!in_array($user['role'], $allowedRoles)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Get search filters
$searchKeyword = $_GET['search'] ?? '';

// Build SQL query to join alumni_profiles with users table and filter by role
$db = Database::getUserDB();

$whereClauses = ["u.role IN ('board', 'head', 'member', 'candidate')"];
$params = [];

// Search term for name (first_name or last_name)
if (!empty($searchKeyword)) {
    $whereClauses[] = "(ap.first_name LIKE ? OR ap.last_name LIKE ?)";
    $searchTerm = '%' . $searchKeyword . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSQL = ' WHERE ' . implode(' AND ', $whereClauses);

$sql = "
    SELECT ap.id, ap.user_id, ap.first_name, ap.last_name, ap.email, 
           ap.position, ap.image_path, u.role
    FROM alumni_profiles ap
    INNER JOIN users u ON ap.user_id = u.id " . $whereSQL . "
    ORDER BY ap.last_name ASC, ap.first_name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$profiles = $stmt->fetchAll();

$title = 'Mitglieder - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Success Message -->
    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_SESSION['success_message']); ?>
    </div>
    <?php 
        unset($_SESSION['success_message']); 
    endif; 
    ?>

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800 mb-2">
            <i class="fas fa-users mr-3 text-blue-600"></i>
            Mitglieder
        </h1>
        <p class="text-gray-600">Unser Team und aktive Mitglieder</p>
    </div>

    <!-- Search Bar -->
    <div class="card p-6 mb-8">
        <form method="GET" action="" class="space-y-4 sm:space-y-0 sm:flex sm:gap-4">
            <!-- Keyword Search -->
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-1 text-blue-600"></i>
                    Nach Name suchen
                </label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    value="<?php echo htmlspecialchars($searchKeyword); ?>"
                    placeholder="Name eingeben..."
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                >
            </div>
            
            <!-- Search Button -->
            <div class="sm:flex sm:items-end">
                <button 
                    type="submit"
                    class="w-full sm:w-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl"
                >
                    <i class="fas fa-search mr-2"></i>
                    Suchen
                </button>
            </div>
        </form>
        
        <!-- Clear Filters -->
        <?php if (!empty($searchKeyword)): ?>
            <div class="mt-4">
                <a href="directory.php" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
                    <i class="fas fa-times-circle mr-1"></i>
                    Filter zurücksetzen
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Results Count -->
    <div class="mb-6">
        <p class="text-gray-600">
            <strong><?php echo count($profiles); ?></strong> 
            <?php echo count($profiles) === 1 ? 'Mitglied' : 'Mitglieder'; ?> gefunden
        </p>
    </div>

    <!-- Member Profiles Grid -->
    <?php if (empty($profiles)): ?>
        <div class="card p-12 text-center">
            <i class="fas fa-user-slash text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-600 mb-2">Keine Mitglieder gefunden</p>
            <p class="text-gray-500">Bitte Suchfilter anpassen</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($profiles as $profile): ?>
                <div class="card p-6 hover:shadow-xl transition-shadow">
                    <!-- Profile Image -->
                    <div class="flex justify-center mb-4">
                        <?php 
                        // Generate initials for fallback
                        $initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
                        $imagePath = !empty($profile['image_path']) ? asset($profile['image_path']) : '';
                        ?>
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-3xl font-bold overflow-hidden shadow-lg">
                            <?php if (!empty($imagePath)): ?>
                                <img 
                                    src="<?php echo $imagePath; ?>" 
                                    alt="<?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>"
                                    class="w-full h-full object-cover"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                                <div style="display:none;" class="w-full h-full flex items-center justify-center text-3xl">
                                    <?php echo htmlspecialchars($initials); ?>
                                </div>
                            <?php else: ?>
                                <?php echo htmlspecialchars($initials); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Name -->
                    <h3 class="text-lg font-bold text-gray-800 text-center mb-2">
                        <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                    </h3>
                    
                    <!-- Position -->
                    <div class="text-center mb-4">
                        <?php if (!empty($profile['position'])): ?>
                        <p class="text-sm text-gray-600 mb-1">
                            <?php echo htmlspecialchars($profile['position']); ?>
                        </p>
                        <?php else: ?>
                        <p class="text-sm text-gray-400 mb-1 italic">
                            Keine Position angegeben
                        </p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-id-badge mr-1"></i>
                            <?php echo htmlspecialchars(ucfirst($profile['role'])); ?>
                        </p>
                    </div>
                    
                    <!-- Contact Button -->
                    <a 
                        href="mailto:<?php echo htmlspecialchars($profile['email']); ?>"
                        class="block w-full text-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-md"
                    >
                        <i class="fas fa-envelope mr-2"></i>
                        Kontakt
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
