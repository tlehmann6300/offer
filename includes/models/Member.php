<?php
declare(strict_types=1);

/**
 * Member Model
 * Handles the logic for the Active Member Directory
 */

require_once __DIR__ . '/../database.php';

class Member {
    
    /**
     * Active member roles (excludes 'alumni')
     */
    const ACTIVE_ROLES = ['board', 'head', 'member', 'candidate', 'admin'];
    
    /**
     * Get all active members with optional search and role filtering
     * 
     * @param string|null $search Optional search term for first_name, last_name, company, or industry
     * @param string|null $filterRole Optional role filter (e.g., 'candidate', 'member', 'board', etc.)
     * @return array Array of active member profiles with user information
     */
    public static function getAllActive(?string $search = null, ?string $filterRole = null): array {
        $userDb = Database::getUserDB();
        $contentDb = Database::getContentDB();
        
        // Build the query with cross-database JOIN
        $roleList = "'" . implode("', '", self::ACTIVE_ROLES) . "'";
        $whereClauses = ["u.role IN ($roleList)"];
        $params = [];
        
        // Add search filter if provided
        if ($search !== null && $search !== '') {
            $whereClauses[] = "(ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.company LIKE ? OR ap.industry LIKE ? OR ap.study_program LIKE ? OR ap.studiengang LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Add role filter if provided
        if ($filterRole !== null && $filterRole !== '') {
            $whereClauses[] = "u.role = ?";
            $params[] = $filterRole;
        }
        
        $whereSQL = implode(' AND ', $whereClauses);
        
        // Query using content_db connection with database prefix for user table
        $sql = "
            SELECT 
                u.id as user_id,
                u.email,
                u.role,
                u.created_at as user_created_at,
                ap.id as profile_id,
                ap.first_name,
                ap.last_name,
                ap.mobile_phone,
                ap.linkedin_url,
                ap.xing_url,
                ap.industry,
                ap.company,
                ap.position,
                ap.studiengang,
                ap.study_program,
                ap.semester,
                ap.angestrebter_abschluss,
                ap.degree,
                ap.graduation_year,
                ap.about_me,
                ap.image_path,
                ap.created_at,
                ap.updated_at
            FROM " . DB_USER_NAME . ".users u
            INNER JOIN alumni_profiles ap ON u.id = ap.user_id
            WHERE " . $whereSQL . "
            ORDER BY ap.last_name ASC, ap.first_name ASC
        ";
        
        $stmt = $contentDb->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get statistics of member counts per role
     * Returns count of members per role for active roles only (not alumni)
     * 
     * @return array Associative array with role names as keys and counts as values
     */
    public static function getStatistics(): array {
        $db = Database::getUserDB();
        
        $roleList = "'" . implode("', '", self::ACTIVE_ROLES) . "'";
        $sql = "
            SELECT role, COUNT(*) as count
            FROM users
            WHERE role IN ($roleList)
            GROUP BY role
            ORDER BY role ASC
        ";
        
        $stmt = $db->prepare($sql);
        
        $stmt->execute();
        $results = $stmt->fetchAll();
        
        // Convert to associative array with role as key
        $statistics = [];
        foreach ($results as $row) {
            $statistics[$row['role']] = (int)$row['count'];
        }
        
        return $statistics;
    }
}
