<?php
/**
 * Post-Deploy Cleanup Script
 * 
 * This script securely deletes development and setup files from the server root
 * to harden security after deployment.
 * 
 * Files to delete:
 * - setup_admin.php
 * - debug_paths.php
 * - debug_500.php
 * - debug_white_screen.php
 * - check_system_final.php
 * - test_mail_live.php
 * - deploy_migrations.php
 * - check_location.php
 * 
 * The script will:
 * 1. Check if each file exists before attempting deletion
 * 2. Output an HTML list showing which files were 'Deleted' (Green) or 'Not Found' (Gray)
 * 3. Self-destruct at the end (delete itself)
 * 
 * IMPORTANT: This script will delete itself after execution!
 */

// Set execution time limit
set_time_limit(60);

// List of files to delete
$filesToDelete = [
    'setup_admin.php',
    'debug_paths.php',
    'debug_500.php',
    'debug_white_screen.php',
    'check_system_final.php',
    'test_mail_live.php',
    'deploy_migrations.php',
    'check_location.php'
];

// Initialize counters
$deletedCount = 0;
$notFoundCount = 0;

// Start HTML output
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Post-Deploy Cleanup - IBC Intranet</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gray-100'>
    <div class='container mx-auto px-4 py-8'>
        <div class='bg-white rounded-lg shadow-lg p-6 max-w-4xl mx-auto'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6'>
                <i class='fas fa-shield-alt text-blue-600 mr-2'></i>
                Post-Deploy Security Cleanup
            </h1>
            <p class='text-gray-600 mb-8'>Removing development and setup files to harden security...</p>
            
            <div class='mb-6'>
                <h2 class='text-xl font-semibold text-gray-800 mb-3'>
                    <i class='fas fa-file-code text-red-600 mr-2'></i>
                    Development & Setup Files Cleanup
                </h2>
                <ul class='space-y-2'>
<?php

// Process each file
foreach ($filesToDelete as $filename) {
    $filePath = __DIR__ . '/' . $filename;
    
    if (file_exists($filePath) && is_file($filePath)) {
        // File exists - attempt to delete it
        try {
            if (unlink($filePath)) {
                $deletedCount++;
                echo "<li class='text-green-600 ml-4'>";
                echo "<i class='fas fa-check-circle mr-2'></i>";
                echo "<strong>Deleted:</strong> " . htmlspecialchars($filename);
                echo "</li>\n";
            } else {
                // Deletion failed
                echo "<li class='text-red-600 ml-4'>";
                echo "<i class='fas fa-exclamation-circle mr-2'></i>";
                echo "<strong>Error:</strong> Failed to delete " . htmlspecialchars($filename);
                echo "</li>\n";
            }
        } catch (Exception $e) {
            // Exception during deletion
            echo "<li class='text-red-600 ml-4'>";
            echo "<i class='fas fa-exclamation-circle mr-2'></i>";
            echo "<strong>Error:</strong> " . htmlspecialchars($filename) . " - " . htmlspecialchars($e->getMessage());
            echo "</li>\n";
        }
    } else {
        // File not found
        $notFoundCount++;
        echo "<li class='text-gray-500 ml-4'>";
        echo "<i class='fas fa-info-circle mr-2'></i>";
        echo "<strong>Not Found:</strong> " . htmlspecialchars($filename);
        echo "</li>\n";
    }
}

?>
                </ul>
            </div>
            
            <!-- Summary -->
            <div class='border-t pt-6 mt-8'>
                <h2 class='text-2xl font-bold text-gray-800 mb-4'>
                    <i class='fas fa-clipboard-check text-green-600 mr-2'></i>
                    Cleanup Summary
                </h2>
                
                <div class='grid grid-cols-1 md:grid-cols-2 gap-4 mb-6'>
                    <div class='bg-green-50 p-4 rounded-lg'>
                        <p class='text-green-800 font-semibold'>Files Deleted</p>
                        <p class='text-3xl font-bold text-green-600'><?php echo $deletedCount; ?></p>
                    </div>
                    <div class='bg-gray-50 p-4 rounded-lg'>
                        <p class='text-gray-800 font-semibold'>Files Not Found</p>
                        <p class='text-3xl font-bold text-gray-600'><?php echo $notFoundCount; ?></p>
                    </div>
                </div>
                
                <div class='bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-4'>
                    <div class='flex items-center'>
                        <i class='fas fa-exclamation-triangle text-yellow-600 mr-3'></i>
                        <div>
                            <p class='text-yellow-800 font-semibold'>Self-Destruct in Progress</p>
                            <p class='text-yellow-700 text-sm'>This script is now deleting itself...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php

// Self-destruct: Delete this script itself
try {
    $selfPath = __FILE__;
    if (unlink($selfPath)) {
        // Script successfully deleted itself
        // This message won't be seen in the HTML output since the script is already being sent to the browser
        error_log("Post-deploy cleanup script successfully self-destructed.");
    }
} catch (Exception $e) {
    error_log("Failed to self-destruct post-deploy cleanup script: " . $e->getMessage());
}
