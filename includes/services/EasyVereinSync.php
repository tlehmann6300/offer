<?php
/**
 * EasyVereinSync Service
 * Handles one-way synchronization from EasyVerein (External) to Intranet (Local)
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/Inventory.php';

class EasyVereinSync {
    
    /**
     * Fetch data from EasyVerein
     * Mock implementation returning hardcoded JSON array
     * 
     * @return array Array of inventory items with EasyVerein data structure
     */
    public function fetchDataFromEasyVerein() {
        // Mock implementation with typical inventory items
        return [
            [
                'EasyVereinID' => 'EV-001',
                'Name' => 'Laptop Dell XPS 15',
                'Description' => 'High-performance laptop for development work',
                'TotalQuantity' => 5,
                'SerialNumber' => 'DL-XPS-2024-001'
            ],
            [
                'EasyVereinID' => 'EV-002',
                'Name' => 'Projektor Epson EB-2250U',
                'Description' => 'Full HD projector for presentations',
                'TotalQuantity' => 3,
                'SerialNumber' => 'EP-EB-2024-002'
            ],
            [
                'EasyVereinID' => 'EV-003',
                'Name' => 'Whiteboard 180x120cm',
                'Description' => 'Mobile whiteboard with stand',
                'TotalQuantity' => 8,
                'SerialNumber' => null
            ],
            [
                'EasyVereinID' => 'EV-004',
                'Name' => 'Konferenzmikrofon Jabra Speak',
                'Description' => 'USB conference speakerphone',
                'TotalQuantity' => 10,
                'SerialNumber' => 'JB-SPK-2024-004'
            ],
            [
                'EasyVereinID' => 'EV-005',
                'Name' => 'Tablet iPad Air',
                'Description' => 'Tablet for mobile presentations',
                'TotalQuantity' => 7,
                'SerialNumber' => 'AP-IPA-2024-005'
            ]
        ];
    }
    
    /**
     * Synchronize inventory from EasyVerein to local database
     * 
     * This method:
     * 1. Fetches data from EasyVerein
     * 2. For each item:
     *    - If exists locally (by easyverein_id): Updates master data fields
     *    - If not exists: Creates new inventory record
     * 3. For deletions: Marks items with easyverein_id not in fetch result as archived
     * 
     * @param int $userId User ID performing the sync (for audit trail)
     * @return array Result with statistics (created, updated, archived, errors)
     */
    public function sync($userId = null) {
        $db = Database::getContentDB();
        
        // If no userId provided, use system user (0)
        if ($userId === null) {
            $userId = 0;
        }
        
        $stats = [
            'created' => 0,
            'updated' => 0,
            'archived' => 0,
            'errors' => []
        ];
        
        try {
            // Fetch data from EasyVerein
            $easyvereinItems = $this->fetchDataFromEasyVerein();
            
            // Track EasyVerein IDs that are present in this sync
            $currentEasyVereinIds = [];
            
            // Process each item from EasyVerein
            foreach ($easyvereinItems as $evItem) {
                try {
                    $easyvereinId = $evItem['EasyVereinID'];
                    $currentEasyVereinIds[] = $easyvereinId;
                    
                    // Check if item exists locally by easyverein_id
                    $stmt = $db->prepare("
                        SELECT id, name, description, current_stock, serial_number
                        FROM inventory
                        WHERE easyverein_id = ?
                    ");
                    $stmt->execute([$easyvereinId]);
                    $existingItem = $stmt->fetch();
                    
                    if ($existingItem) {
                        // Update existing item using Inventory model with sync flag
                        // This allows the update to bypass Master Data protection
                        $updateData = [
                            'name' => $evItem['Name'],
                            'description' => $evItem['Description'],
                            'current_stock' => $evItem['TotalQuantity'],
                            'serial_number' => $evItem['SerialNumber'],
                            'last_synced_at' => date('Y-m-d H:i:s'),
                            'is_archived_in_easyverein' => 0
                        ];
                        
                        // Use Inventory::update() with $isSyncUpdate = true to bypass protection
                        Inventory::update($existingItem['id'], $updateData, $userId, true);
                        
                        $stats['updated']++;
                        
                        // Log update in history
                        $this->logSyncHistory(
                            $existingItem['id'],
                            $userId,
                            'sync_update',
                            $existingItem['current_stock'],
                            $evItem['TotalQuantity'],
                            'Synchronized from EasyVerein',
                            json_encode([
                                'old_name' => $existingItem['name'],
                                'new_name' => $evItem['Name'],
                                'easyverein_id' => $easyvereinId
                            ])
                        );
                        
                    } else {
                        // Create new item
                        $stmt = $db->prepare("
                            INSERT INTO inventory (
                                easyverein_id,
                                name,
                                description,
                                serial_number,
                                current_stock,
                                last_synced_at,
                                is_archived_in_easyverein
                            ) VALUES (?, ?, ?, ?, ?, NOW(), 0)
                        ");
                        
                        $stmt->execute([
                            $easyvereinId,
                            $evItem['Name'],
                            $evItem['Description'],
                            $evItem['SerialNumber'],
                            $evItem['TotalQuantity']
                        ]);
                        
                        $newItemId = $db->lastInsertId();
                        $stats['created']++;
                        
                        // Log creation in history
                        $this->logSyncHistory(
                            $newItemId,
                            $userId,
                            'sync_create',
                            null,
                            $evItem['TotalQuantity'],
                            'Created from EasyVerein sync',
                            json_encode([
                                'easyverein_id' => $easyvereinId,
                                'name' => $evItem['Name']
                            ])
                        );
                    }
                    
                } catch (Exception $e) {
                    $stats['errors'][] = "Error processing item '{$evItem['Name']}' (EV-ID: {$evItem['EasyVereinID']}): " . $e->getMessage();
                }
            }
            
            // Handle deletions: Mark items with easyverein_id NOT in current fetch as archived
            // This should run even if currentEasyVereinIds is empty (i.e., EasyVerein returns no items)
            if (!empty($currentEasyVereinIds)) {
                $placeholders = str_repeat('?,', count($currentEasyVereinIds) - 1) . '?';
                
                // Find items with easyverein_id that are not in the current sync
                $stmt = $db->prepare("
                    SELECT id, easyverein_id, name
                    FROM inventory
                    WHERE easyverein_id IS NOT NULL
                    AND easyverein_id NOT IN ($placeholders)
                    AND is_archived_in_easyverein = 0
                ");
                $stmt->execute($currentEasyVereinIds);
                $itemsToArchive = $stmt->fetchAll();
            } else {
                // If EasyVerein returns no items, archive all items with easyverein_id
                $stmt = $db->prepare("
                    SELECT id, easyverein_id, name
                    FROM inventory
                    WHERE easyverein_id IS NOT NULL
                    AND is_archived_in_easyverein = 0
                ");
                $stmt->execute();
                $itemsToArchive = $stmt->fetchAll();
            }
            
            // Archive items not found in current sync
            foreach ($itemsToArchive as $item) {
                // Mark as archived (soft delete)
                $stmt = $db->prepare("
                    UPDATE inventory
                    SET is_archived_in_easyverein = 1,
                        last_synced_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$item['id']]);
                
                $stats['archived']++;
                
                // Log archival
                $this->logSyncHistory(
                    $item['id'],
                    $userId,
                    'sync_archive',
                    null,
                    null,
                    'Archived - no longer in EasyVerein',
                    json_encode([
                        'easyverein_id' => $item['easyverein_id'],
                        'name' => $item['name']
                    ])
                );
            }
            
        } catch (Exception $e) {
            $stats['errors'][] = "Sync failed: " . $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Log synchronization history
     * 
     * @param int $itemId Inventory item ID
     * @param int $userId User ID performing the action
     * @param string $changeType Type of change (sync_create, sync_update, sync_archive)
     * @param mixed $oldStock Old stock value
     * @param mixed $newStock New stock value
     * @param string $reason Reason for the change
     * @param string $comment Additional comment/data
     */
    private function logSyncHistory($itemId, $userId, $changeType, $oldStock, $newStock, $reason, $comment) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            INSERT INTO inventory_history (
                item_id,
                user_id,
                change_type,
                old_stock,
                new_stock,
                change_amount,
                reason,
                comment
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $changeAmount = null;
        if ($oldStock !== null && $newStock !== null) {
            $changeAmount = $newStock - $oldStock;
        }
        
        $stmt->execute([
            $itemId,
            $userId,
            $changeType,
            $oldStock,
            $newStock,
            $changeAmount,
            $reason,
            $comment
        ]);
    }
}
