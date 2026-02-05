<?php
declare(strict_types=1);

/**
 * Alumni Model
 * Manages alumni profile data and operations
 */

class Alumni {
    
    /**
     * Get profile by user ID
     * 
     * @param int $userId The user ID
     * @return array|false Profile data or false if not found
     */
    public static function getProfileByUserId(int $userId) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT id, user_id, first_name, last_name, email, mobile_phone, 
                   linkedin_url, xing_url, industry, company, position, 
                   image_path, last_verified_at, created_at, updated_at
            FROM alumni_profiles 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update or create profile (upsert)
     * 
     * @param int $userId The user ID
     * @param array $data Profile data to upsert
     * @return bool True on success
     * @throws Exception On database error
     */
    public static function updateOrCreateProfile(int $userId, array $data): bool {
        $db = Database::getContentDB();
        
        // Sanitize image_path if provided
        if (isset($data['image_path'])) {
            $data['image_path'] = self::sanitizeImagePath($data['image_path']);
        }
        
        // Check if profile exists
        $existing = self::getProfileByUserId($userId);
        
        if ($existing) {
            // Update existing profile
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'first_name', 'last_name', 'email', 'mobile_phone',
                'linkedin_url', 'xing_url', 'industry', 'company', 
                'position', 'image_path'
            ];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return true; // No fields to update
            }
            
            $values[] = $userId;
            $sql = "UPDATE alumni_profiles SET " . implode(', ', $fields) . " WHERE user_id = ?";
            
            $stmt = $db->prepare($sql);
            return $stmt->execute($values);
        } else {
            // Insert new profile
            $requiredFields = ['first_name', 'last_name', 'email', 'company', 'position'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO alumni_profiles 
                (user_id, first_name, last_name, email, mobile_phone, 
                 linkedin_url, xing_url, industry, company, position, image_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $userId,
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['mobile_phone'] ?? null,
                $data['linkedin_url'] ?? null,
                $data['xing_url'] ?? null,
                $data['industry'] ?? null,
                $data['company'],
                $data['position'],
                $data['image_path'] ?? null
            ]);
        }
    }
    
    /**
     * Sanitize image path to prevent directory traversal
     * 
     * @param string $imagePath The image path to sanitize
     * @return string Sanitized image path
     */
    private static function sanitizeImagePath(string $imagePath): string {
        // Remove any directory traversal attempts
        $imagePath = str_replace(['../', '..\\', '../', '..\\\\'], '', $imagePath);
        
        // Ensure path starts with uploads/ if it doesn't already
        if (!str_starts_with($imagePath, 'uploads/')) {
            $imagePath = 'uploads/' . ltrim($imagePath, '/');
        }
        
        return $imagePath;
    }
    
    /**
     * Search profiles with filters
     * 
     * @param array $filters Array of filters: search (name), industry, company
     * @return array Array of matching profiles
     */
    public static function searchProfiles(array $filters = []): array {
        $db = Database::getContentDB();
        
        $whereClauses = [];
        $params = [];
        
        // Search term for name (first_name or last_name)
        if (!empty($filters['search'])) {
            $whereClauses[] = "(first_name LIKE ? OR last_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Filter by industry
        if (!empty($filters['industry'])) {
            $whereClauses[] = "industry = ?";
            $params[] = $filters['industry'];
        }
        
        // Filter by company
        if (!empty($filters['company'])) {
            $whereClauses[] = "company = ?";
            $params[] = $filters['company'];
        }
        
        $whereSQL = '';
        if (!empty($whereClauses)) {
            $whereSQL = ' WHERE ' . implode(' AND ', $whereClauses);
        }
        
        $sql = "
            SELECT id, user_id, first_name, last_name, email, mobile_phone, 
                   linkedin_url, xing_url, industry, company, position, 
                   image_path, last_verified_at, created_at, updated_at
            FROM alumni_profiles" . $whereSQL . "
            ORDER BY last_name ASC, first_name ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all unique industries for filter dropdown
     * 
     * @return array Array of unique industry names
     */
    public static function getAllIndustries(): array {
        $db = Database::getContentDB();
        $stmt = $db->query("
            SELECT DISTINCT industry 
            FROM alumni_profiles 
            WHERE industry IS NOT NULL AND industry != ''
            ORDER BY industry ASC
        ");
        
        $industries = [];
        while ($row = $stmt->fetch()) {
            $industries[] = $row['industry'];
        }
        
        return $industries;
    }
    
    /**
     * Get profiles where last_verified_at is older than specified months
     * Used by email bot to send verification reminders
     * 
     * @param int $months Number of months (default: 12)
     * @return array Array of outdated profiles
     */
    public static function getOutdatedProfiles(int $months = 12): array {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT id, user_id, first_name, last_name, email, mobile_phone, 
                   linkedin_url, xing_url, industry, company, position, 
                   image_path, last_verified_at, created_at, updated_at
            FROM alumni_profiles 
            WHERE last_verified_at < DATE_SUB(NOW(), INTERVAL ? MONTH)
            ORDER BY last_verified_at ASC
        ");
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }
    
    /**
     * Verify profile by updating last_verified_at to current timestamp
     * 
     * @param int $userId The user ID
     * @return bool True on success
     */
    public static function verifyProfile(int $userId): bool {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            UPDATE alumni_profiles 
            SET last_verified_at = NOW() 
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
}
