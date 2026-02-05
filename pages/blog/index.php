<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/BlogPost.php';
require_once __DIR__ . '/../../src/Database.php';

// Check authentication
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();
$userRole = $_SESSION['user_role'] ?? 'member';

// Get filter from query parameters
$filterCategory = $_GET['category'] ?? null;

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get posts
$allPosts = BlogPost::getAll($perPage + 1, $offset, $filterCategory); // Get one extra to check if there's a next page
$hasNextPage = count($allPosts) > $perPage;
$posts = array_slice($allPosts, 0, $perPage);

// Get like and comment counts for each post
$contentDb = Database::getContentDB();
foreach ($posts as &$post) {
    // Get like count
    $likeStmt = $contentDb->prepare("SELECT COUNT(*) FROM blog_likes WHERE post_id = ?");
    $likeStmt->execute([$post['id']]);
    $post['like_count'] = $likeStmt->fetchColumn();
    
    // Get comment count
    $commentStmt = $contentDb->prepare("SELECT COUNT(*) FROM blog_comments WHERE post_id = ?");
    $commentStmt->execute([$post['id']]);
    $post['comment_count'] = $commentStmt->fetchColumn();
}

// Define categories with colors
$categories = [
    'Allgemein' => 'gray',
    'IT' => 'blue',
    'Marketing' => 'purple',
    'Human Resources' => 'green',
    'Qualitätsmanagement' => 'yellow',
    'Akquise' => 'red'
];

// Function to get category color classes
function getCategoryColor($category) {
    $colors = [
        'Allgemein' => 'bg-gray-100 text-gray-800',
        'IT' => 'bg-blue-100 text-blue-800',
        'Marketing' => 'bg-purple-100 text-purple-800',
        'Human Resources' => 'bg-green-100 text-green-800',
        'Qualitätsmanagement' => 'bg-yellow-100 text-yellow-800',
        'Akquise' => 'bg-red-100 text-red-800'
    ];
    return $colors[$category] ?? 'bg-gray-100 text-gray-800';
}

// Function to truncate text
function truncateText($text, $maxLength = 150) {
    $text = strip_tags($text);
    if (strlen($text) <= $maxLength) {
        return $text;
    }
    return substr($text, 0, $maxLength) . '...';
}

$title = 'News & Updates - IBC Intranet';
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-newspaper mr-3 text-blue-600"></i>
                News & Updates
            </h1>
            <p class="text-gray-600">Bleiben Sie über wichtige Neuigkeiten und Updates informiert</p>
        </div>
        
        <?php if (BlogPost::canAuth($userRole)): ?>
            <a href="edit.php" 
               class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                Neuen Beitrag erstellen
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter Bar -->
    <div class="mb-6 card p-4">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-gray-700 font-semibold mr-2">
                <i class="fas fa-filter mr-2"></i>
                Kategorie:
            </span>
            <a href="index.php" 
               class="px-4 py-2 rounded-lg font-medium transition-all <?php echo $filterCategory === null ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Alle
            </a>
            <?php foreach ($categories as $cat => $color): ?>
                <a href="index.php?category=<?php echo urlencode($cat); ?>" 
                   class="px-4 py-2 rounded-lg font-medium transition-all <?php echo $filterCategory === $cat ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- News Grid -->
    <?php if (empty($posts)): ?>
        <div class="card p-8 text-center">
            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-600">Keine Beiträge gefunden</p>
            <?php if ($filterCategory): ?>
                <p class="text-gray-500 mt-2">Versuchen Sie einen anderen Filter</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($posts as $post): ?>
                <a href="view.php?id=<?php echo (int)$post['id']; ?>" 
                   class="card overflow-hidden flex flex-col hover:shadow-xl transition-shadow cursor-pointer"
                   aria-label="Beitrag lesen: <?php echo htmlspecialchars($post['title']); ?>">
                    <!-- Image -->
                    <div class="h-48 bg-gray-200 overflow-hidden">
                        <?php if (!empty($post['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100">
                                <i class="fas fa-newspaper text-6xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-6 flex-1 flex flex-col">
                        <!-- Category Badge -->
                        <div class="mb-3">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?php echo getCategoryColor($post['category']); ?>">
                                <?php echo htmlspecialchars($post['category']); ?>
                            </span>
                        </div>
                        
                        <!-- Title -->
                        <h3 class="text-xl font-bold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </h3>
                        
                        <!-- Date -->
                        <div class="text-sm text-gray-500 mb-3">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            <?php 
                                $date = new DateTime($post['created_at']);
                                echo $date->format('d.m.Y');
                            ?>
                        </div>
                        
                        <!-- Excerpt -->
                        <p class="text-gray-600 text-sm mb-4 flex-1">
                            <?php echo htmlspecialchars(truncateText($post['content'])); ?>
                        </p>
                        
                        <!-- Footer -->
                        <div class="pt-4 border-t border-gray-200 flex items-center justify-between text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                                <span><?php echo htmlspecialchars($post['author_email']); ?></span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="flex items-center">
                                    <i class="fas fa-heart mr-1 text-red-500"></i>
                                    <?php echo $post['like_count']; ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-comment mr-1 text-blue-500"></i>
                                    <?php echo $post['comment_count']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($page > 1 || $hasNextPage): ?>
        <div class="mt-8 flex justify-center gap-4">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $filterCategory ? '&category=' . urlencode($filterCategory) : ''; ?>" 
                   class="px-6 py-3 bg-white text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all shadow-md">
                    <i class="fas fa-chevron-left mr-2"></i>
                    Zurück
                </a>
            <?php endif; ?>
            
            <div class="px-6 py-3 bg-blue-100 text-blue-800 rounded-lg font-semibold">
                Seite <?php echo $page; ?>
            </div>
            
            <?php if ($hasNextPage): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $filterCategory ? '&category=' . urlencode($filterCategory) : ''; ?>" 
                   class="px-6 py-3 bg-white text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-all shadow-md">
                    Weiter
                    <i class="fas fa-chevron-right ml-2"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
