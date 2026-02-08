<?php
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../includes/models/Alumni.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Access Control: Allow all logged-in active users
if (!Auth::check()) {
    header('Location: ../auth/login.php');
    exit;
}

$user = Auth::user();

// Check if user has one of the allowed active roles
$allowedRoles = ['admin', 'board', 'head', 'member', 'candidate'];
if (!in_array($user['role'], $allowedRoles)) {
    header('Location: ../dashboard/index.php');
    exit;
}

// Get profile ID from URL
$profileId = $_GET['id'] ?? null;

if (!$profileId) {
    header('Location: index.php');
    exit;
}

// Get profile data
$profile = Alumni::getProfileById((int)$profileId);

if (!$profile) {
    $_SESSION['error_message'] = 'Profil nicht gefunden';
    header('Location: index.php');
    exit;
}

// Check if this profile belongs to an active member
$allowedViewRoles = ['admin', 'board', 'head', 'member', 'candidate'];
if (!in_array($profile['role'], $allowedViewRoles)) {
    // This is an alumni profile, redirect to alumni view or directory
    header('Location: ../alumni/index.php');
    exit;
}

$title = htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) . ' - IBC Intranet';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="index.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            Zurück zum Mitgliederverzeichnis
        </a>
    </div>

    <!-- Profile Card -->
    <div class="card p-8">
        <!-- Profile Header -->
        <div class="flex flex-col md:flex-row gap-6 mb-8">
            <!-- Profile Image -->
            <div class="flex justify-center md:justify-start">
                <?php 
                $initials = strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1));
                $imagePath = !empty($profile['image_path']) ? asset($profile['image_path']) : '';
                ?>
                <div class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-4xl font-bold overflow-hidden shadow-lg">
                    <?php if (!empty($imagePath)): ?>
                        <img 
                            src="<?php echo htmlspecialchars($imagePath); ?>" 
                            alt="<?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>"
                            class="w-full h-full object-cover"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >
                        <div style="display:none;" class="w-full h-full flex items-center justify-center text-4xl bg-gradient-to-br from-blue-400 to-blue-600">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                    <?php else: ?>
                        <?php echo htmlspecialchars($initials); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Info -->
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?>
                </h1>
                
                <!-- Role Badge -->
                <?php
                $roleBadgeColors = [
                    'board' => 'bg-purple-100 text-purple-800 border-purple-300',
                    'head' => 'bg-blue-100 text-blue-800 border-blue-300',
                    'member' => 'bg-green-100 text-green-800 border-green-300',
                    'candidate' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                    'admin' => 'bg-red-100 text-red-800 border-red-300'
                ];
                
                $roleNames = [
                    'admin' => 'Administrator',
                    'board' => 'Vorstand',
                    'alumni_board' => 'Alumni-Vorstand',
                    'head' => 'Ressortleiter',
                    'member' => 'Mitglied',
                    'alumni' => 'Alumni',
                    'candidate' => 'Anwärter'
                ];
                
                $badgeClass = $roleBadgeColors[$profile['role']] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                $roleName = $roleNames[$profile['role']] ?? ucfirst($profile['role']);
                ?>
                <div class="mb-4">
                    <span class="inline-block px-4 py-2 text-sm font-semibold rounded-full border <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($roleName); ?>
                    </span>
                </div>

                <!-- Professional Info -->
                <?php if (!empty($profile['position']) || !empty($profile['company'])): ?>
                <div class="mb-4">
                    <?php if (!empty($profile['position'])): ?>
                    <p class="text-lg text-gray-700 mb-1">
                        <i class="fas fa-briefcase mr-2 text-gray-500"></i>
                        <?php echo htmlspecialchars($profile['position']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($profile['company'])): ?>
                    <p class="text-md text-gray-600">
                        <i class="fas fa-building mr-2 text-gray-500"></i>
                        <?php echo htmlspecialchars($profile['company']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Industry -->
                <?php if (!empty($profile['industry'])): ?>
                <p class="text-sm text-gray-600 mb-4">
                    <i class="fas fa-industry mr-2 text-gray-500"></i>
                    <?php echo htmlspecialchars($profile['industry']); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="border-t pt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-address-card mr-2 text-blue-600"></i>
                Kontaktinformationen
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <?php if (!empty($profile['email'])): ?>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white mr-3">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">E-Mail</p>
                        <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" 
                           class="text-blue-600 hover:text-blue-800 font-medium">
                            <?php echo htmlspecialchars($profile['email']); ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- LinkedIn -->
                <?php if (!empty($profile['linkedin_url'])): ?>
                    <?php
                    // Validate LinkedIn URL
                    $linkedinUrl = $profile['linkedin_url'];
                    $isValidLinkedIn = (
                        strpos($linkedinUrl, 'https://linkedin.com') === 0 ||
                        strpos($linkedinUrl, 'https://www.linkedin.com') === 0 ||
                        strpos($linkedinUrl, 'http://linkedin.com') === 0 ||
                        strpos($linkedinUrl, 'http://www.linkedin.com') === 0
                    );
                    ?>
                    <?php if ($isValidLinkedIn): ?>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white mr-3">
                            <i class="fab fa-linkedin-in"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">LinkedIn</p>
                            <a href="<?php echo htmlspecialchars($linkedinUrl); ?>" 
                               target="_blank"
                               rel="noopener noreferrer"
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                Profil ansehen
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/templates/main_layout.php';
?>
