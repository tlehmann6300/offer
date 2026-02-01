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
                   l.name as location_name
            FROM inventory i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN locations l ON i.location_id = l.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all items with filters
     */
    public static function getAll($filters = []) {
        $db = Database::getContentDB();
        $sql = "
            SELECT i.*, c.name as category_name, c.color as category_color, 
                   l.name as location_name
            FROM inventory i
            LEFT JOIN categories c ON i.category_id = c.id
            LEFT JOIN locations l ON i.location_id = l.id
            WHERE 1=1
        ";
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND i.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['location_id'])) {
            $sql .= " AND i.location_id = ?";
            $params[] = $filters['location_id'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (i.name LIKE ? OR i.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $sql .= " AND i.current_stock <= i.min_stock";
        }
        
        $sql .= " ORDER BY i.name ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
     */
    public static function update($id, $data, $userId) {
        $db = Database::getContentDB();
        
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
}
