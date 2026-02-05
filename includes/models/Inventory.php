<?php
/**
 * Inventory Model
 * Manages inventory items and operations
 */

class Inventory {
    
    /**
     * Get item by ID
     */
    public static function getById($id) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT i.*, c.name as category_name, c.color as category_color, 
                   l.name as location_name,
                   i.current_stock as quantity,
                   (i.current_stock - COALESCE(SUM(r.amount), 0)) as available_quantity
            FROM inventory i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN locations l ON i.location_id = l.id
            LEFT JOIN rentals r ON i.id = r.item_id AND r.actual_return IS NULL
            WHERE i.id = ?
            GROUP BY i.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get available stock for an item
     * 
     * Calculates available stock as: (Synced Total Stock from DB) - (Count of Active/Reserved Local Loans)
     * 
     * @param int $id Item ID
     * @return int Available stock quantity
     */
    public static function getAvailableStock($id) {
        $db = Database::getContentDB();
        
        // Get total stock from inventory and sum of active rentals
        // Note: r.actual_return IS NULL identifies active (unreturned) rentals
        $stmt = $db->prepare("
            SELECT 
                i.current_stock,
                COALESCE(SUM(r.amount), 0) as active_rentals
            FROM inventory i
            LEFT JOIN rentals r ON i.id = r.item_id AND r.actual_return IS NULL
            WHERE i.id = ?
            GROUP BY i.id, i.current_stock
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return 0;
        }
        
        // Formula: Total Stock - Active Rentals
        $availableStock = $result['current_stock'] - $result['active_rentals'];
        
        // Ensure non-negative
        return max(0, $availableStock);
    }

    /**
     * Get all items with filters
     */
    public static function getAll($filters = []) {
        $db = Database::getContentDB();
        
        // Build WHERE clauses based on filters
        $whereClauses = [];
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $whereClauses[] = "i.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['location_id'])) {
            $whereClauses[] = "i.location_id = ?";
            $params[] = $filters['location_id'];
        }
        
        if (!empty($filters['search'])) {
            $whereClauses[] = "(i.name LIKE ? OR i.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['low_stock'])) {
            $whereClauses[] = "i.current_stock <= i.min_stock AND i.min_stock > 0";
        }
        
        $whereSQL = '';
        if (!empty($whereClauses)) {
            $whereSQL = ' WHERE ' . implode(' AND ', $whereClauses);
        }
        
        // SQL query with correct table and column names
        // Note: quantity is an alias for current_stock for backward compatibility
        // available_quantity = current_stock - active rentals
        $sql = "SELECT i.*, 
                       c.name as category_name, 
                       c.color as category_color,
                       l.name as location_name,
                       i.current_stock as quantity,
                       (i.current_stock - COALESCE(SUM(r.amount), 0)) as available_quantity
                FROM inventory i
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN locations l ON i.location_id = l.id
                LEFT JOIN rentals r ON i.id = r.item_id AND r.actual_return IS NULL" 
                . $whereSQL . "
                GROUP BY i.id, i.easyverein_id, i.name, i.description, i.serial_number, i.category_id, i.location_id, 
                         i.status, i.current_stock, i.min_stock, i.unit, i.unit_price, i.purchase_date, 
                         i.image_path, i.notes, i.created_at, i.updated_at, i.last_synced_at, i.is_archived_in_easyverein,
                         c.name, c.color, l.name
                ORDER BY i.name ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new item
     */
    public static function create($data, $userId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            INSERT INTO inventory (name, description, category_id, location_id, current_stock, min_stock, unit, unit_price, image_path, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['category_id'] ?? null,
            $data['location_id'] ?? null,
            $data['current_stock'] ?? 0,
            $data['min_stock'] ?? 0,
            $data['unit'] ?? 'Stück',
            $data['unit_price'] ?? 0,
            $data['image_path'] ?? null,
            $data['notes'] ?? null
        ]);
        
        $itemId = $db->lastInsertId();
        
        // Log creation
        self::logHistory($itemId, $userId, 'create', null, $data['current_stock'] ?? 0, null, 'Item created', null);
        
        return $itemId;
    }

    /**
     * Update item
     * 
     * Protects EasyVerein-synced items from direct Master Data modifications
     * Master Data fields: name, description, current_stock
     * Local Operational fields: location_id, notes, category_id, etc.
     * 
     * @param int $id Item ID
     * @param array $data Fields to update
     * @param int $userId User ID performing the update
     * @param bool $isSyncUpdate Set to true when called from EasyVereinSync (default: false)
     * @throws Exception If attempting to modify Master Data on EasyVerein-synced items
     * @return bool Success status
     */
    public static function update($id, $data, $userId, $isSyncUpdate = false) {
        $db = Database::getContentDB();
        
        // Check if this item is synced with EasyVerein (unless this IS a sync update)
        if (!$isSyncUpdate) {
            $stmt = $db->prepare("SELECT easyverein_id FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            
            if ($item && !empty($item['easyverein_id'])) {
                // This item is synced with EasyVerein
                // Check if trying to modify Master Data fields
                $masterDataFields = ['name', 'description', 'current_stock'];
                $attemptedMasterDataChanges = array_intersect(array_keys($data), $masterDataFields);
                
                if (!empty($attemptedMasterDataChanges)) {
                    throw new Exception(
                        "Cannot modify Master Data fields (" . implode(', ', $attemptedMasterDataChanges) . ") " .
                        "for EasyVerein-synced items. These fields are managed by EasyVerein sync. " .
                        "You can only modify Local Operational Data (location_id, notes, category_id, etc.)."
                    );
                }
            }
        }
        
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        $values[] = $id;
        $sql = "UPDATE inventory SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute($values);
        
        // Log update
        self::logHistory($id, $userId, 'update', null, null, null, 'Item updated', json_encode($data));
        
        return $result;
    }

    /**
     * Delete item
     */
    public static function delete($id, $userId) {
        $db = Database::getContentDB();
        
        // Log deletion
        self::logHistory($id, $userId, 'delete', null, null, null, 'Item deleted', null);
        
        $stmt = $db->prepare("DELETE FROM inventory WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Adjust stock
     */
    public static function adjustStock($id, $amount, $reason, $comment, $userId) {
        $db = Database::getContentDB();
        
        // Get current stock
        $stmt = $db->prepare("SELECT current_stock FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return false;
        }
        
        $oldStock = $item['current_stock'];
        $newStock = $oldStock + $amount;
        
        // Prevent negative stock
        if ($newStock < 0) {
            $newStock = 0;
        }
        
        // Update stock
        $stmt = $db->prepare("UPDATE inventory SET current_stock = ? WHERE id = ?");
        $stmt->execute([$newStock, $id]);
        
        // Log adjustment
        self::logHistory($id, $userId, 'adjustment', $oldStock, $newStock, $amount, $reason, $comment);
        
        return true;
    }

    /**
     * Get item history
     */
    public static function getHistory($itemId, $limit = 50) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT * FROM inventory_history
            WHERE item_id = ?
            ORDER BY timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$itemId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Log history entry
     */
    private static function logHistory($itemId, $userId, $changeType, $oldStock, $newStock, $changeAmount, $reason, $comment) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            INSERT INTO inventory_history (item_id, user_id, change_type, old_stock, new_stock, change_amount, reason, comment)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$itemId, $userId, $changeType, $oldStock, $newStock, $changeAmount, $reason, $comment]);
    }

    /**
     * Get dashboard statistics
     */
    public static function getDashboardStats() {
        $db = Database::getContentDB();
        
        $stats = [];
        
        // Total items
        $stmt = $db->query("SELECT COUNT(*) as total FROM inventory");
        $stats['total_items'] = $stmt->fetch()['total'];
        
        // Total value
        $stmt = $db->query("SELECT SUM(current_stock * unit_price) as total_value FROM inventory");
        $stats['total_value'] = $stmt->fetch()['total_value'] ?? 0;
        
        // Low stock items
        $stmt = $db->query("SELECT COUNT(*) as low_stock FROM inventory WHERE current_stock <= min_stock AND min_stock > 0");
        $stats['low_stock'] = $stmt->fetch()['low_stock'];
        
        // Recently moved items
        $stmt = $db->query("
            SELECT COUNT(DISTINCT item_id) as recent_moves 
            FROM inventory_history 
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['recent_moves'] = $stmt->fetch()['recent_moves'];
        
        return $stats;
    }

    /**
     * Get categories
     */
    public static function getCategories() {
        $db = Database::getContentDB();
        $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get locations
     */
    public static function getLocations() {
        $db = Database::getContentDB();
        $stmt = $db->query("SELECT * FROM locations ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Create category
     */
    public static function createCategory($name, $description = null, $color = '#3B82F6') {
        $db = Database::getContentDB();
        $stmt = $db->prepare("INSERT INTO categories (name, description, color) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $color]);
        return $db->lastInsertId();
    }

    /**
     * Create location
     */
    public static function createLocation($name, $description = null, $address = null) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("INSERT INTO locations (name, description, address) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $address]);
        return $db->lastInsertId();
    }

    /**
     * Checkout item (Borrow/Remove from inventory)
     */
    public static function checkoutItem($itemId, $userId, $quantity, $purpose, $destination = null, $expectedReturnDate = null) {
        $db = Database::getContentDB();
        
        // Get current stock
        $stmt = $db->prepare("SELECT current_stock, name FROM inventory WHERE id = ?");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        
        if (!$item) {
            return ['success' => false, 'message' => 'Artikel nicht gefunden'];
        }
        
        // Check if enough stock available
        if ($item['current_stock'] < $quantity) {
            return ['success' => false, 'message' => 'Nicht genügend Bestand verfügbar'];
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Create rental record (using rentals table)
            $stmt = $db->prepare("
                INSERT INTO rentals (item_id, user_id, amount, expected_return)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$itemId, $userId, $quantity, $expectedReturnDate]);
            
            // Update stock
            $newStock = $item['current_stock'] - $quantity;
            $stmt = $db->prepare("UPDATE inventory SET current_stock = ? WHERE id = ?");
            $stmt->execute([$newStock, $itemId]);
            
            // Log checkout in history
            // Note: change_amount is negative to indicate stock reduction
            self::logHistory($itemId, $userId, 'checkout', $item['current_stock'], $newStock, -$quantity, 'Ausgeliehen', $purpose . ($destination ? ' - ' . $destination : ''));
            
            $db->commit();
            return ['success' => true, 'message' => 'Artikel erfolgreich ausgeliehen'];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Fehler beim Ausleihen: ' . $e->getMessage()];
        }
    }

    /**
     * Check-in item (Return to inventory)
     */
    public static function checkinItem($rentalId, $returnedQuantity, $isDefective, $defectiveQuantity = 0, $defectiveReason = null) {
        $db = Database::getContentDB();
        
        // Get rental record (using rentals table)
        $stmt = $db->prepare("
            SELECT r.*, i.current_stock, i.name 
            FROM rentals r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.id = ? AND r.actual_return IS NULL
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch();
        
        if (!$rental) {
            return ['success' => false, 'message' => 'Ausleihe nicht gefunden oder bereits zurückgegeben'];
        }
        
        // Validate quantities
        if ($returnedQuantity > $rental['amount']) {
            return ['success' => false, 'message' => 'Rückgabemenge kann nicht größer als ausgeliehene Menge sein'];
        }
        
        if ($isDefective && $defectiveQuantity > $returnedQuantity) {
            return ['success' => false, 'message' => 'Defekte Menge kann nicht größer als Rückgabemenge sein'];
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            $goodQuantity = $returnedQuantity - $defectiveQuantity;
            $newStock = $rental['current_stock'] + $goodQuantity;
            
            // Update rental record
            $status = $isDefective && $defectiveQuantity > 0 ? 'defective' : 'returned';
            $stmt = $db->prepare("
                UPDATE rentals 
                SET actual_return = NOW(), defect_notes = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$defectiveReason, $status, $rentalId]);
            
            // Update stock (only add back good items)
            $stmt = $db->prepare("UPDATE inventory SET current_stock = ? WHERE id = ?");
            $stmt->execute([$newStock, $rental['item_id']]);
            
            // Log check-in
            $comment = "Rückgabe: {$returnedQuantity} Stück";
            if ($defectiveQuantity > 0) {
                $comment .= " (davon {$defectiveQuantity} defekt: {$defectiveReason})";
            }
            self::logHistory($rental['item_id'], $rental['user_id'], 'checkin', $rental['current_stock'], $newStock, $goodQuantity, 'Zurückgegeben', $comment);
            
            // If items are defective, log write-off
            if ($defectiveQuantity > 0) {
                self::logHistory($rental['item_id'], $rental['user_id'], 'writeoff', $newStock, $newStock, -$defectiveQuantity, 'Ausschuss', $defectiveReason);
            }
            
            $db->commit();
            return ['success' => true, 'message' => 'Artikel erfolgreich zurückgegeben'];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Fehler bei der Rückgabe: ' . $e->getMessage()];
        }
    }

    /**
     * Get active checkouts for an item
     */
    public static function getItemCheckouts($itemId) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT r.*
            FROM rentals r
            WHERE r.item_id = ? AND r.actual_return IS NULL
            ORDER BY r.rented_at DESC
        ");
        $stmt->execute([$itemId]);
        $rentals = $stmt->fetchAll();
        
        // Fetch user information from user database
        if (!empty($rentals)) {
            $userDb = Database::getUserDB();
            $userIds = array_column($rentals, 'user_id');
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $userStmt = $userDb->prepare("SELECT id, email FROM users WHERE id IN ($placeholders)");
            $userStmt->execute($userIds);
            $users = [];
            foreach ($userStmt->fetchAll() as $user) {
                $users[$user['id']] = $user;
            }
            
            // Add user info to rentals
            foreach ($rentals as &$rental) {
                $rental['user_email'] = $users[$rental['user_id']]['email'] ?? 'Unknown';
            }
        }
        
        return $rentals;
    }

    /**
     * Get all checkouts for a user
     */
    public static function getUserCheckouts($userId, $includeReturned = false) {
        $db = Database::getContentDB();
        $sql = "
            SELECT r.*, i.name as item_name, i.unit
            FROM rentals r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.user_id = ?
        ";
        
        if (!$includeReturned) {
            $sql .= " AND r.actual_return IS NULL";
        }
        
        $sql .= " ORDER BY r.rented_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all rentals for a user
     * This method returns rentals from the rentals table with proper column names
     */
    public static function getRentalsByUser($userId, $includeReturned = false) {
        // Validate userId
        if (!is_numeric($userId) || $userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }
        
        $db = Database::getContentDB();
        $sql = "
            SELECT 
                r.id,
                r.item_id,
                r.user_id,
                r.amount as quantity,
                r.rented_at,
                r.expected_return,
                r.actual_return as returned_at,
                r.status,
                r.defect_notes,
                i.name as item_name,
                i.unit
            FROM rentals r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.user_id = ?
        ";
        
        if (!$includeReturned) {
            $sql .= " AND r.actual_return IS NULL";
        }
        
        $sql .= " ORDER BY r.rented_at DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get checkout by ID
     */
    public static function getCheckoutById($rentalId) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT r.*, i.name as item_name, i.unit, i.current_stock
            FROM rentals r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.id = ?
        ");
        $stmt->execute([$rentalId]);
        return $stmt->fetch();
    }

    /**
     * Get in-stock statistics for dashboard
     * Returns total items currently in stock (available inventory)
     */
    public static function getInStockStats() {
        $db = Database::getContentDB();
        
        $stats = [];
        
        // Total items in stock (sum of all current_stock)
        $stmt = $db->query("SELECT SUM(current_stock) as total_in_stock FROM inventory");
        $stats['total_in_stock'] = $stmt->fetch()['total_in_stock'] ?? 0;
        
        // Total unique items in stock
        $stmt = $db->query("SELECT COUNT(*) as unique_items FROM inventory WHERE current_stock > 0");
        $stats['unique_items_in_stock'] = $stmt->fetch()['unique_items'];
        
        // Total value in stock
        $stmt = $db->query("SELECT SUM(current_stock * unit_price) as total_value FROM inventory");
        $stats['total_value_in_stock'] = $stmt->fetch()['total_value'] ?? 0;
        
        return $stats;
    }

    /**
     * Get checked-out statistics for dashboard
     * Returns: ['total_items_out' => (int), 'unique_users' => (int), 'overdue' => (int), 'checkouts' => array]
     */
    public static function getCheckedOutStats() {
        $db = Database::getContentDB();
        
        // Calculate total items out (sum of all amounts from active rentals)
        $stmt = $db->query("
            SELECT 
                COALESCE(SUM(r.amount), 0) as total_items_out,
                COUNT(DISTINCT r.user_id) as unique_users
            FROM rentals r
            WHERE r.actual_return IS NULL
        ");
        $stats = $stmt->fetch();
        
        // Calculate overdue items (expected_return < current date and not yet returned)
        $stmt = $db->query("
            SELECT COUNT(*) as overdue
            FROM rentals r
            WHERE r.actual_return IS NULL
            AND r.expected_return IS NOT NULL
            AND r.expected_return < CURDATE()
        ");
        $overdueResult = $stmt->fetch();
        
        // Get detailed checkout information
        $stmt = $db->query("
            SELECT 
                r.id,
                r.item_id,
                r.user_id,
                r.amount,
                r.rented_at,
                r.expected_return,
                i.name as item_name,
                i.unit
            FROM rentals r
            JOIN inventory i ON r.item_id = i.id
            WHERE r.actual_return IS NULL
            ORDER BY r.rented_at DESC
        ");
        $checkouts = $stmt->fetchAll();
        
        // Fetch user information from user database
        if (!empty($checkouts)) {
            $userDb = Database::getUserDB();
            $userIds = array_unique(array_column($checkouts, 'user_id'));
            
            if (!empty($userIds)) {
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                $userStmt = $userDb->prepare("SELECT id, email FROM users WHERE id IN ($placeholders)");
                $userStmt->execute($userIds);
                $users = [];
                foreach ($userStmt->fetchAll() as $user) {
                    $users[$user['id']] = $user;
                }
                
                // Add user info to checkouts
                foreach ($checkouts as &$checkout) {
                    $checkout['borrower_email'] = isset($users[$checkout['user_id']]) 
                        ? $users[$checkout['user_id']]['email'] 
                        : 'Unbekannt';
                }
            }
        }
        
        return [
            'total_items_out' => (int)$stats['total_items_out'],
            'unique_users' => (int)$stats['unique_users'],
            'overdue' => (int)$overdueResult['overdue'],
            'checkouts' => $checkouts
        ];
    }

    /**
     * Get write-off statistics for this month
     * Returns items reported as write-off (loss/defect) this month
     */
    public static function getWriteOffStatsThisMonth() {
        $db = Database::getContentDB();
        
        // Get all write-offs this month
        $stmt = $db->query("
            SELECT 
                ih.id, ih.item_id, ih.user_id, ih.change_amount, ih.reason, ih.comment, ih.timestamp,
                i.name as item_name, i.unit
            FROM inventory_history ih
            JOIN inventory i ON ih.item_id = i.id
            WHERE ih.change_type = 'writeoff'
            AND MONTH(ih.timestamp) = MONTH(CURRENT_DATE())
            AND YEAR(ih.timestamp) = YEAR(CURRENT_DATE())
            ORDER BY ih.timestamp DESC
        ");
        $writeoffs = $stmt->fetchAll();
        
        // Fetch user information from user database
        if (!empty($writeoffs)) {
            $userDb = Database::getUserDB();
            $userIds = array_unique(array_column($writeoffs, 'user_id'));
            
            if (!empty($userIds)) {
                $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
                $userStmt = $userDb->prepare("SELECT id, email FROM users WHERE id IN ($placeholders)");
                $userStmt->execute($userIds);
                $users = [];
                foreach ($userStmt->fetchAll() as $user) {
                    $users[$user['id']] = $user;
                }
                
                // Add user info to writeoffs
                foreach ($writeoffs as &$writeoff) {
                    $writeoff['reported_by_email'] = $users[$writeoff['user_id']]['email'] ?? 'Unbekannt';
                }
            }
        }
        
        // Calculate statistics
        $stats = [
            'total_writeoffs' => count($writeoffs),
            'total_quantity_lost' => abs(array_sum(array_column($writeoffs, 'change_amount'))),
            'writeoffs' => $writeoffs
        ];
        
        return $stats;
    }

    /**
     * Import inventory items from JSON data
     * 
     * @param array $data Array of inventory items from JSON
     * @param int $userId User ID performing the import
     * @return array Result with 'success', 'imported', 'skipped', and 'errors' keys
     */
    public static function importFromJson($data, $userId) {
        $db = Database::getContentDB();
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        // Validate that data is an array
        if (!is_array($data)) {
            return [
                'success' => false,
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Invalid JSON format: expected array of items']
            ];
        }
        
        // Process each item
        foreach ($data as $index => $item) {
            // Validate required fields
            if (empty($item['name'])) {
                $errors[] = "Item at index $index: 'name' is required";
                $skipped++;
                continue;
            }
            
            if (empty($item['category'])) {
                $errors[] = "Item at index $index: 'category' is required";
                $skipped++;
                continue;
            }
            
            // Get status with default
            $status = $item['status'] ?? 'available';
            $validStatuses = ['available', 'in_use', 'maintenance', 'retired'];
            if (!in_array($status, $validStatuses)) {
                $errors[] = "Item at index $index: Invalid status '$status'. Must be one of: " . implode(', ', $validStatuses);
                $skipped++;
                continue;
            }
            
            // Check if serial_number exists and is duplicate
            if (!empty($item['serial_number'])) {
                $stmt = $db->prepare("SELECT id, name FROM inventory WHERE serial_number = ?");
                $stmt->execute([$item['serial_number']]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $errors[] = "Item at index $index ('{$item['name']}'): Serial number '{$item['serial_number']}' already exists for item '{$existing['name']}' (ID: {$existing['id']})";
                    $skipped++;
                    continue;
                }
            }
            
            try {
                // Get or create category
                $categoryId = null;
                $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt->execute([$item['category']]);
                $category = $stmt->fetch();
                
                if ($category) {
                    $categoryId = $category['id'];
                } else {
                    // Create new category
                    $categoryId = self::createCategory($item['category']);
                }
                
                // Get or create location if provided
                $locationId = null;
                if (!empty($item['location'])) {
                    $stmt = $db->prepare("SELECT id FROM locations WHERE name = ?");
                    $stmt->execute([$item['location']]);
                    $location = $stmt->fetch();
                    
                    if ($location) {
                        $locationId = $location['id'];
                    } else {
                        // Create new location
                        $locationId = self::createLocation($item['location']);
                    }
                }
                
                // Validate and format purchase_date if provided
                $purchaseDate = null;
                if (!empty($item['purchase_date'])) {
                    // Try to parse the date
                    $timestamp = strtotime($item['purchase_date']);
                    if ($timestamp === false) {
                        $errors[] = "Item at index $index ('{$item['name']}'): Invalid purchase_date format '{$item['purchase_date']}'";
                        $skipped++;
                        continue;
                    }
                    $purchaseDate = date('Y-m-d', $timestamp);
                }
                
                // Insert item
                $stmt = $db->prepare("
                    INSERT INTO inventory (
                        name, description, serial_number, category_id, location_id, 
                        status, current_stock, purchase_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $item['name'],
                    $item['description'] ?? null,
                    $item['serial_number'] ?? null,
                    $categoryId,
                    $locationId,
                    $status,
                    1, // Default stock of 1 for imported items
                    $purchaseDate
                ]);
                
                $itemId = $db->lastInsertId();
                
                // Log creation
                self::logHistory(
                    $itemId, 
                    $userId, 
                    'create', 
                    null, 
                    1, 
                    null, 
                    'Imported from JSON', 
                    json_encode(['original_data' => $item])
                );
                
                $imported++;
            } catch (Exception $e) {
                $errors[] = "Item at index $index ('{$item['name']}'): " . $e->getMessage();
                $skipped++;
            }
        }
        
        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }
    
    /**
     * Sync inventory from EasyVerein
     * 
     * Wrapper method to easily call EasyVereinSync::sync()
     * 
     * @param int $userId User ID performing the sync (for audit trail)
     * @return array Result with statistics (created, updated, archived, errors)
     */
    public static function syncFromEasyVerein($userId) {
        require_once __DIR__ . '/../services/EasyVereinSync.php';
        
        $sync = new EasyVereinSync();
        return $sync->sync($userId);
    }
}
