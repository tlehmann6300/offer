<?php
/**
 * Example usage of EasyVereinSync Service
 * 
 * This script demonstrates how to use the EasyVereinSync class
 * to synchronize inventory items from EasyVerein to the local database.
 */

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/services/EasyVereinSync.php';

// Create an instance of the sync service
$sync = new EasyVereinSync();

echo "=== EasyVerein Synchronization ===\n\n";

// Step 1: Preview what data would be synced
echo "Step 1: Fetching data from EasyVerein (mock)...\n";
$easyvereinData = $sync->fetchDataFromEasyVerein();

echo "Found " . count($easyvereinData) . " items in EasyVerein:\n";
foreach ($easyvereinData as $item) {
    echo "  - {$item['Name']} (ID: {$item['EasyVereinID']}, Qty: {$item['TotalQuantity']})\n";
}
echo "\n";

// Step 2: Perform the actual synchronization
// NOTE: Replace 1 with the actual user ID performing the sync
$userId = 1; // Use the ID of the user running the sync

echo "Step 2: Synchronizing inventory...\n";
$result = $sync->sync($userId);

// Step 3: Display results
echo "\nSynchronization Results:\n";
echo "  ✓ Created: {$result['created']} items\n";
echo "  ✓ Updated: {$result['updated']} items\n";
echo "  ✓ Archived: {$result['archived']} items\n";

if (!empty($result['errors'])) {
    echo "\n⚠ Errors encountered:\n";
    foreach ($result['errors'] as $error) {
        echo "  - $error\n";
    }
} else {
    echo "\n✓ Synchronization completed successfully!\n";
}

echo "\n=== Synchronization Complete ===\n";

// Optional: Display what happens on subsequent syncs
echo "\n=== Notes ===\n";
echo "• First sync: Creates new items from EasyVerein\n";
echo "• Subsequent syncs: Updates existing items with latest data from EasyVerein\n";
echo "• Master data (name, description, stock) is overwritten from EasyVerein\n";
echo "• Local operational data (location, category) is preserved\n";
echo "• Items removed from EasyVerein are marked as archived (soft delete)\n";
echo "• All changes are logged in inventory_history table\n";
