<?php
/**
 * Database Connection Handler
 * Manages connections to both User and Content databases
 */

require_once __DIR__ . '/../config/config.php';

class Database {
    private static $userConnection = null;
    private static $contentConnection = null;

    /**
     * Get User Database Connection
     */
    public static function getUserDB() {
        if (self::$userConnection === null) {
            try {
                self::$userConnection = new PDO(
                    "mysql:host=" . DB_USER_HOST . ";dbname=" . DB_USER_NAME . ";charset=utf8mb4",
                    DB_USER_USER,
                    DB_USER_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("User DB Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return self::$userConnection;
    }

    /**
     * Get Content Database Connection
     */
    public static function getContentDB() {
        if (self::$contentConnection === null) {
            try {
                self::$contentConnection = new PDO(
                    "mysql:host=" . DB_CONTENT_HOST . ";dbname=" . DB_CONTENT_NAME . ";charset=utf8mb4",
                    DB_CONTENT_USER,
                    DB_CONTENT_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Content DB Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return self::$contentConnection;
    }

    /**
     * Close all database connections
     */
    public static function closeAll() {
        self::$userConnection = null;
        self::$contentConnection = null;
    }
}
