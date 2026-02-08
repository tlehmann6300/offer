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
        
        // Step 1: Get alumni profiles from Content DB with search filters
        $whereClauses = [];
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
        
        $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
        
        // Query to get all alumni profiles
        $sql = "
            SELECT 
                ap.user_id,
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
            FROM alumni_profiles ap
            " . $whereSQL . "
            ORDER BY ap.last_name ASC, ap.first_name ASC
        ";
        
        $stmt = $contentDb->prepare($sql);
        $stmt->execute($params);
        $profiles = $stmt->fetchAll();
        
        if (empty($profiles)) {
            return [];
        }
        
        // Step 2: Collect all user_ids from profiles
        $userIds = array_column($profiles, 'user_id');
        
        // Step 3: Query User DB to get user information (email, role) for these user_ids
        // Apply role filter at this stage
        $userWhereClauses = ["u.id IN (" . implode(',', array_fill(0, count($userIds), '?')) . ")"];
        $userParams = $userIds;
        
        // Add role filter for ACTIVE_ROLES (using placeholders for safety)
        $rolePlaceholders = implode(',', array_fill(0, count(self::ACTIVE_ROLES), '?'));
        $userWhereClauses[] = "u.role IN ($rolePlaceholders)";
        $userParams = array_merge($userParams, self::ACTIVE_ROLES);
        
        // Add specific role filter if provided
        if ($filterRole !== null && $filterRole !== '') {
            $userWhereClauses[] = "u.role = ?";
            $userParams[] = $filterRole;
        }
        
        $userWhereSQL = implode(' AND ', $userWhereClauses);
        
        $userSql = "
            SELECT 
                u.id,
                u.email,
                u.role,
                u.created_at
            FROM users u
            WHERE " . $userWhereSQL . "
        ";
        
        $userStmt = $userDb->prepare($userSql);
        $userStmt->execute($userParams);
        $users = $userStmt->fetchAll();
        
        // Step 4: Create a map of user_id => user data
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['id']] = $user;
        }
        
        // Step 5: Merge profiles with user data
        $result = [];
        foreach ($profiles as $profile) {
            $userId = $profile['user_id'];
            
            // Only include if user exists and meets criteria
            if (isset($userMap[$userId])) {
                $user = $userMap[$userId];
                
                // Merge user data into profile
                $profile['email'] = $user['email'];
                $profile['role'] = $user['role'];
                $profile['user_created_at'] = $user['created_at'];
                
                $result[] = $profile;
            }
        }
        
        return $result;
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
