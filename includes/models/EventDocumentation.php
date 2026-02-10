<?php
/**
 * EventDocumentation Model
 * Manages event documentation for board and alumni_board members
 */

require_once __DIR__ . '/../../src/Database.php';

class EventDocumentation {
    
    /**
     * Get documentation for an event
     * 
     * @param int $eventId Event ID
     * @return array|null Documentation data or null if not found
     */
    public static function getByEventId($eventId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            SELECT * FROM event_documentation 
            WHERE event_id = ?
        ");
        $stmt->execute([$eventId]);
        
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doc && !empty($doc['sales_data'])) {
            // Decode JSON sales data
            $doc['sales_data'] = json_decode($doc['sales_data'], true);
        }
        
        return $doc ?: null;
    }
    
    /**
     * Save or update documentation for an event
     * 
     * @param int $eventId Event ID
     * @param string $calculations Calculations text
     * @param array $salesData Array of sales entries
     * @param int $userId User ID making the update
     * @return bool Success status
     */
    public static function save($eventId, $calculations, $salesData, $userId) {
        $db = Database::getContentDB();
        
        // Encode sales data as JSON
        $salesDataJson = json_encode($salesData);
        
        // Check if documentation exists
        $existing = self::getByEventId($eventId);
        
        if ($existing) {
            // Update existing
            $stmt = $db->prepare("
                UPDATE event_documentation 
                SET calculations = ?, sales_data = ?, updated_by = ?
                WHERE event_id = ?
            ");
            return $stmt->execute([$calculations, $salesDataJson, $userId, $eventId]);
        } else {
            // Insert new
            $stmt = $db->prepare("
                INSERT INTO event_documentation (event_id, calculations, sales_data, updated_by)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$eventId, $calculations, $salesDataJson, $userId]);
        }
    }
}
