<?php
/**
 * Final Cleanup Script
 * Removes temporary files and folders to save storage space
 * 
 * This script:
 * - Deletes the sql/migrations/ folder (all migrations consolidated)
 * - Deletes old .backup, .zip, .tar.gz files in root directory
 * - Deletes setup.sh and import_database.sh scripts
 * - Outputs a list of deleted files
 */

// Set execution time limit
set_time_limit(300);

// Initialize counters and lists
$deletedFiles = [];
$deletedFolders = [];
$errors = [];
$totalSize = 0;

/**
 * Format bytes to human-readable size
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Recursively delete a directory
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return false;
    }
    
    $size = 0;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $fileinfo) {
        if ($fileinfo->isFile()) {
            $size += $fileinfo->getSize();
            unlink($fileinfo->getRealPath());
        } elseif ($fileinfo->isDir()) {
            rmdir($fileinfo->getRealPath());
        }
    }
    
    rmdir($dir);
    return $size;
}

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cleanup Script - IBC Intranet</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gray-100'>
    <div class='container mx-auto px-4 py-8'>
        <div class='bg-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>
                <i class='fas fa-broom text-blue-600 mr-2'></i>
                Final Cleanup Script
            </h1>
            <p class='text-gray-600 mb-8'>Cleaning up temporary files and folders...</p>
";

// 1. Delete sql/migrations/ folder
echo "<div class='mb-6'>
        <h2 class='text-xl font-semibold text-gray-800 mb-3'>
            <i class='fas fa-folder-minus text-yellow-600 mr-2'></i>
            Step 1: Remove sql/migrations/ folder
        </h2>";

$migrationsPath = __DIR__ . '/sql/migrations';
if (file_exists($migrationsPath) && is_dir($migrationsPath)) {
    try {
        $size = deleteDirectory($migrationsPath);
        $totalSize += $size;
        $deletedFolders[] = "sql/migrations/ (" . formatBytes($size) . ")";
        echo "<p class='text-green-600 ml-4'><i class='fas fa-check-circle mr-2'></i>Successfully deleted sql/migrations/ folder (" . formatBytes($size) . ")</p>";
    } catch (Exception $e) {
        $errors[] = "Failed to delete sql/migrations/: " . $e->getMessage();
        echo "<p class='text-red-600 ml-4'><i class='fas fa-exclamation-circle mr-2'></i>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='text-gray-600 ml-4'><i class='fas fa-info-circle mr-2'></i>Folder sql/migrations/ not found or already deleted</p>";
}
echo "</div>";

// 2. Delete old backup files in root directory
echo "<div class='mb-6'>
        <h2 class='text-xl font-semibold text-gray-800 mb-3'>
            <i class='fas fa-file-archive text-orange-600 mr-2'></i>
            Step 2: Remove backup and archive files
        </h2>";

$patterns = ['*.backup', '*.zip', '*.tar.gz', '*.tar'];
foreach ($patterns as $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    if (!empty($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                try {
                    $size = filesize($file);
                    if (unlink($file)) {
                        $totalSize += $size;
                        $deletedFiles[] = basename($file) . " (" . formatBytes($size) . ")";
                        echo "<p class='text-green-600 ml-4'><i class='fas fa-check-circle mr-2'></i>Deleted: " . htmlspecialchars(basename($file)) . " (" . formatBytes($size) . ")</p>";
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to delete " . basename($file) . ": " . $e->getMessage();
                    echo "<p class='text-red-600 ml-4'><i class='fas fa-exclamation-circle mr-2'></i>Error deleting " . htmlspecialchars(basename($file)) . ": " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
        }
    }
}

if (empty($deletedFiles)) {
    echo "<p class='text-gray-600 ml-4'><i class='fas fa-info-circle mr-2'></i>No backup or archive files found in root directory</p>";
}
echo "</div>";

// 3. Delete setup scripts
echo "<div class='mb-6'>
        <h2 class='text-xl font-semibold text-gray-800 mb-3'>
            <i class='fas fa-terminal text-purple-600 mr-2'></i>
            Step 3: Remove setup scripts
        </h2>";

$scriptsToDelete = ['setup.sh', 'import_database.sh'];
foreach ($scriptsToDelete as $script) {
    $scriptPath = __DIR__ . '/' . $script;
    if (file_exists($scriptPath) && is_file($scriptPath)) {
        try {
            $size = filesize($scriptPath);
            if (unlink($scriptPath)) {
                $totalSize += $size;
                $deletedFiles[] = $script . " (" . formatBytes($size) . ")";
                echo "<p class='text-green-600 ml-4'><i class='fas fa-check-circle mr-2'></i>Deleted: " . htmlspecialchars($script) . " (" . formatBytes($size) . ")</p>";
            }
        } catch (Exception $e) {
            $errors[] = "Failed to delete " . $script . ": " . $e->getMessage();
            echo "<p class='text-red-600 ml-4'><i class='fas fa-exclamation-circle mr-2'></i>Error deleting " . htmlspecialchars($script) . ": " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='text-gray-600 ml-4'><i class='fas fa-info-circle mr-2'></i>Script " . htmlspecialchars($script) . " not found or already deleted</p>";
    }
}
echo "</div>";

// Summary
echo "<div class='border-t pt-6 mt-8'>
        <h2 class='text-2xl font-bold text-gray-800 mb-4'>
            <i class='fas fa-clipboard-check text-green-600 mr-2'></i>
            Cleanup Summary
        </h2>
        
        <div class='grid grid-cols-1 md:grid-cols-3 gap-4 mb-6'>
            <div class='bg-blue-50 p-4 rounded-lg'>
                <p class='text-blue-800 font-semibold'>Folders Deleted</p>
                <p class='text-3xl font-bold text-blue-600'>" . count($deletedFolders) . "</p>
            </div>
            <div class='bg-green-50 p-4 rounded-lg'>
                <p class='text-green-800 font-semibold'>Files Deleted</p>
                <p class='text-3xl font-bold text-green-600'>" . count($deletedFiles) . "</p>
            </div>
            <div class='bg-purple-50 p-4 rounded-lg'>
                <p class='text-purple-800 font-semibold'>Space Freed</p>
                <p class='text-3xl font-bold text-purple-600'>" . formatBytes($totalSize) . "</p>
            </div>
        </div>";

if (!empty($deletedFolders)) {
    echo "<div class='mb-4'>
            <h3 class='text-lg font-semibold text-gray-800 mb-2'>Deleted Folders:</h3>
            <ul class='list-disc list-inside ml-4 text-gray-700'>";
    foreach ($deletedFolders as $folder) {
        echo "<li>" . htmlspecialchars($folder) . "</li>";
    }
    echo "</ul></div>";
}

if (!empty($deletedFiles)) {
    echo "<div class='mb-4'>
            <h3 class='text-lg font-semibold text-gray-800 mb-2'>Deleted Files:</h3>
            <ul class='list-disc list-inside ml-4 text-gray-700'>";
    foreach ($deletedFiles as $file) {
        echo "<li>" . htmlspecialchars($file) . "</li>";
    }
    echo "</ul></div>";
}

if (!empty($errors)) {
    echo "<div class='mb-4 bg-red-50 border border-red-200 rounded-lg p-4'>
            <h3 class='text-lg font-semibold text-red-800 mb-2'>
                <i class='fas fa-exclamation-triangle mr-2'></i>Errors:
            </h3>
            <ul class='list-disc list-inside ml-4 text-red-700'>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
}

if (count($deletedFiles) === 0 && count($deletedFolders) === 0 && count($errors) === 0) {
    echo "<div class='bg-yellow-50 border border-yellow-200 rounded-lg p-4'>
            <p class='text-yellow-800'>
                <i class='fas fa-info-circle mr-2'></i>
                No files or folders found to clean up. The system is already clean!
            </p>
          </div>";
} else {
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4'>
            <p class='text-green-800 font-semibold'>
                <i class='fas fa-check-circle mr-2'></i>
                Cleanup completed successfully!
            </p>
          </div>";
}

echo "</div>
        <div class='mt-8 text-center'>
            <a href='index.php' class='inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition'>
                <i class='fas fa-home mr-2'></i>Return to Dashboard
            </a>
        </div>
    </div>
</div>
</body>
</html>";
?>
