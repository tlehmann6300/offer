<?php
/**
 * EasyVerein Synchronization Cron Script
 * 
 * Synchronizes inventory data from EasyVerein API to local database.
 * This script should be scheduled to run every 30 minutes.
 * 
 * Crontab example:
 * */30 * * * * /usr/bin/php /path/to/cron/sync_easyverein.php >> /path/to/logs/easyverein_sync.log 2>&1
 * 
 * Usage: php cron/sync_easyverein.php
 */

// Load required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/services/EasyVereinSync.php';

// Output start message
echo "=== EasyVerein Inventory Synchronization ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Create an instance of the sync service
    $syncService = new EasyVereinSync();
    
    // Perform the synchronization (userId = 0 for system/cron)
    echo "Fetching data from EasyVerein API...\n";
    $result = $syncService->sync(0);
    
    // Display results
    echo "\n=== Synchronization Results ===\n";
    echo "Created: {$result['created']} items\n";
    echo "Updated: {$result['updated']} items\n";
    echo "Archived: {$result['archived']} items\n";
    
    if (!empty($result['errors'])) {
        echo "\nErrors encountered:\n";
        foreach ($result['errors'] as $error) {
            echo "  - $error\n";
        }
    } else {
        echo "\nSynchronization completed successfully!\n";
    }
    
} catch (Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
echo "=== End of Synchronization ===\n";
