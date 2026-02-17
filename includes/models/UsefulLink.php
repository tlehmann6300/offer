<?php
/**
 * UsefulLink Model
 * Manages useful links data and operations
 */

require_once __DIR__ . '/../database.php';

class UsefulLink {

    /**
     * Get all useful links, sorted by created_at DESC
     *
     * @return array Array of useful links
     */
    public static function getAll() {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                SELECT id, title, url, description, created_by, created_at
                FROM useful_links
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching useful links: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new useful link
     *
     * @param array $data Link data (title, url, description)
     * @param int $userId User ID who is creating the link
     * @return bool True on success
     */
    public static function create($data, $userId) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("
                INSERT INTO useful_links (title, url, description, created_by)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['title'],
                $data['url'],
                $data['description'] ?? '',
                $userId
            ]);
        } catch (Exception $e) {
            error_log("Error creating useful link: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a useful link by ID
     *
     * @param int $id Link ID
     * @return bool True on success
     */
    public static function delete($id) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("DELETE FROM useful_links WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting useful link: " . $e->getMessage());
            return false;
        }
    }
}
