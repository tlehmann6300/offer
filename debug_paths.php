<?php
/**
 * Debug Paths Script
 * Temporary diagnostic tool to check which classes and paths exist
 * Shows the current state of the file structure and available classes
 */

// Set the base directory
$baseDir = __DIR__;

// Function to format path checks
function checkPath($path, $label) {
    global $baseDir;
    $fullPath = $baseDir . '/' . $path;
    $exists = file_exists($fullPath);
    $type = '';
    
    if ($exists) {
        if (is_file($fullPath)) {
            $size = filesize($fullPath);
            $type = "File (" . number_format($size) . " bytes)";
        } elseif (is_dir($fullPath)) {
            $type = "Directory";
        }
    }
    
    return [
        'label' => $label,
        'path' => $path,
        'exists' => $exists,
        'type' => $type,
        'fullPath' => $fullPath
    ];
}

// Check various paths
$pathChecks = [
    // New src/ structure
    checkPath('src/Auth.php', 'src/Auth.php'),
    checkPath('src/Database.php', 'src/Database.php'),
    checkPath('src/CalendarService.php', 'src/CalendarService.php'),
    checkPath('src/MailService.php', 'src/MailService.php'),
    
    // Old includes/ structure
    checkPath('includes/handlers/AuthHandler.php', 'includes/handlers/AuthHandler.php'),
    checkPath('includes/database.php', 'includes/database.php'),
    checkPath('includes/models/User.php', 'includes/models/User.php'),
    
    // Config files
    checkPath('config/config.php', 'config/config.php'),
    checkPath('config/db.php', 'config/db.php (if exists)'),
    
    // Admin pages
    checkPath('pages/admin/db_maintenance.php', 'pages/admin/db_maintenance.php'),
    checkPath('pages/admin/users.php', 'pages/admin/users.php'),
    
    // Cleanup scripts
    checkPath('cleanup_final.php', 'cleanup_final.php'),
    checkPath('cleanup_system.php', 'cleanup_system.php'),
];

// Try to load some classes to check if they work
$classChecks = [];

// Try loading src/Auth.php
try {
    if (file_exists($baseDir . '/src/Auth.php')) {
        require_once $baseDir . '/src/Auth.php';
        $classChecks[] = [
            'class' => 'Auth',
            'exists' => class_exists('Auth'),
            'methods' => class_exists('Auth') ? get_class_methods('Auth') : []
        ];
        $classChecks[] = [
            'class' => 'AuthHandler',
            'exists' => class_exists('AuthHandler'),
            'methods' => class_exists('AuthHandler') ? get_class_methods('AuthHandler') : []
        ];
    }
} catch (Exception $e) {
    $classChecks[] = [
        'class' => 'Auth/AuthHandler',
        'exists' => false,
        'error' => $e->getMessage()
    ];
}

// Try loading src/Database.php
try {
    if (file_exists($baseDir . '/src/Database.php') && !class_exists('Database')) {
        require_once $baseDir . '/src/Database.php';
    }
    $classChecks[] = [
        'class' => 'Database',
        'exists' => class_exists('Database'),
        'methods' => class_exists('Database') ? get_class_methods('Database') : []
    ];
} catch (Exception $e) {
    $classChecks[] = [
        'class' => 'Database',
        'exists' => false,
        'error' => $e->getMessage()
    ];
}

// Output HTML
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Paths - IBC Intranet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">
                <i class="fas fa-search text-blue-600 mr-2"></i>
                Debug Paths Script
            </h1>
            <p class="text-gray-600 mb-8">Checking file structure and class availability...</p>
            
            <!-- File Path Checks -->
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-folder-open text-yellow-600 mr-2"></i>
                    File Path Checks
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Path</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pathChecks as $check): ?>
                            <tr class="<?php echo $check['exists'] ? 'bg-green-50' : 'bg-red-50'; ?>">
                                <td class="px-4 py-3 text-sm font-mono">
                                    <?php echo htmlspecialchars($check['path']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if ($check['exists']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> EXISTS
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> NOT FOUND
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($check['type']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Class Availability Checks -->
            <div class="mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-code text-purple-600 mr-2"></i>
                    Class Availability Checks
                </h2>
                <div class="space-y-4">
                    <?php foreach ($classChecks as $check): ?>
                    <div class="border rounded-lg p-4 <?php echo $check['exists'] ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50'; ?>">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold <?php echo $check['exists'] ? 'text-green-800' : 'text-red-800'; ?>">
                                <?php echo htmlspecialchars($check['class']); ?>
                            </h3>
                            <?php if ($check['exists']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i> LOADED
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i> NOT AVAILABLE
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($check['error'])): ?>
                            <div class="text-sm text-red-700 mt-2">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Error: <?php echo htmlspecialchars($check['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($check['methods'])): ?>
                            <div class="mt-3">
                                <p class="text-sm font-medium text-gray-700 mb-1">Available Methods:</p>
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (array_slice($check['methods'], 0, 10) as $method): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-white border border-gray-300 text-gray-700">
                                            <?php echo htmlspecialchars($method); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (count($check['methods']) > 10): ?>
                                        <span class="text-xs text-gray-500">...and <?php echo count($check['methods']) - 10; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Summary -->
            <div class="border-t pt-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-clipboard-check text-green-600 mr-2"></i>
                    Summary
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <p class="text-blue-800 font-semibold text-sm">Total Paths Checked</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo count($pathChecks); ?></p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-green-800 font-semibold text-sm">Paths Found</p>
                        <p class="text-3xl font-bold text-green-600">
                            <?php echo count(array_filter($pathChecks, function($c) { return $c['exists']; })); ?>
                        </p>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <p class="text-purple-800 font-semibold text-sm">Classes Available</p>
                        <p class="text-3xl font-bold text-purple-600">
                            <?php echo count(array_filter($classChecks, function($c) { return $c['exists']; })); ?>
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-blue-800 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> This is a temporary diagnostic script. Remove it after verifying the setup.
                    </p>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <a href="index.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                    <i class="fas fa-home mr-2"></i>Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
