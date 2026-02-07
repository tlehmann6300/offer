<?php
declare(strict_types=1);

/**
 * Alumni Model
 * Manages alumni profile data and operations
 */

require_once __DIR__ . '/../database.php';

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
                   studiengang, study_program, semester, angestrebter_abschluss, 
                   degree, graduation_year, about_me,
                   image_path, last_verified_at, last_reminder_sent_at, created_at, updated_at
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
                'position', 'image_path', 'studiengang', 'study_program', 
                'semester', 'angestrebter_abschluss', 'degree', 
                'graduation_year', 'about_me'
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
            // Insert new profile - only first_name, last_name, email are required
            // company and position are optional now for candidates/members
            $requiredFields = ['first_name', 'last_name', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO alumni_profiles 
                (user_id, first_name, last_name, email, mobile_phone, 
                 linkedin_url, xing_url, industry, company, position, image_path,
                 studiengang, study_program, semester, angestrebter_abschluss, 
                 degree, graduation_year, about_me)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $data['company'] ?? null,
                $data['position'] ?? null,
                $data['image_path'] ?? null,
                $data['studiengang'] ?? null,
                $data['study_program'] ?? null,
                $data['semester'] ?? null,
                $data['angestrebter_abschluss'] ?? null,
                $data['degree'] ?? null,
                $data['graduation_year'] ?? null,
                $data['about_me'] ?? null
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
        // Reject paths that contain traversal attempts
        // First pattern catches standalone '..' at start, second catches '/..' or '\..'
        if (preg_match('/\.\./', $imagePath) || 
            preg_match('/[\/\\\\]\.\./', $imagePath) ||
            str_contains($imagePath, "\0") ||
            str_starts_with($imagePath, '/')) {
            // If path contains traversal attempts or null bytes, use only the basename
            $imagePath = basename($imagePath);
        }
        
        // Additional loop-based sanitization as defense-in-depth
        // Handles edge cases where basename might not catch everything
        do {
            $previousPath = $imagePath;
            $imagePath = str_replace(['../', '..\\'], '', $imagePath);
        } while ($imagePath !== $previousPath);
        
        // Ensure path starts with uploads/ if it doesn't already
        if (!str_starts_with($imagePath, 'uploads/')) {
            $imagePath = 'uploads/' . ltrim($imagePath, '/\\');
        }
        
        return $imagePath;
    }
    
    /**
     * Search profiles with filters
     * Only shows profiles where user role is 'alumni'
     * 
     * @param array $filters Array of filters: search (name/position/company/industry), industry
     * @return array Array of matching profiles
     */
    public static function searchProfiles(array $filters = []): array {
        $contentDb = Database::getContentDB();
        $userDb = Database::getUserDB();
        
        $whereClauses = [];
        $params = [];
        
        // Always filter by 'alumni' role
        $whereClauses[] = "u.role = 'alumni'";
        
        // Search term filters by: Name OR Position OR Company OR Industry
        if (!empty($filters['search'])) {
            $whereClauses[] = "(ap.first_name LIKE ? OR ap.last_name LIKE ? OR ap.position LIKE ? OR ap.company LIKE ? OR ap.industry LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Additional filter by industry (for dropdown filter)
        if (!empty($filters['industry'])) {
            $whereClauses[] = "ap.industry LIKE ?";
            $params[] = '%' . $filters['industry'] . '%';
        }
        
        // Additional filter by company (if needed)
        if (!empty($filters['company'])) {
            $whereClauses[] = "ap.company LIKE ?";
            $params[] = '%' . $filters['company'] . '%';
        }
        
        $whereSQL = ' WHERE ' . implode(' AND ', $whereClauses);
        
        // Join with users table to filter by role
        $sql = "
            SELECT ap.id, ap.user_id, ap.first_name, ap.last_name, ap.email, ap.mobile_phone, 
                   ap.linkedin_url, ap.xing_url, ap.industry, ap.company, ap.position, 
                   ap.studiengang, ap.study_program, ap.semester, ap.angestrebter_abschluss, 
                   ap.degree, ap.graduation_year, ap.about_me,
                   ap.image_path, ap.last_verified_at, ap.last_reminder_sent_at, ap.created_at, ap.updated_at
            FROM alumni_profiles ap
            INNER JOIN " . DB_USER_NAME . ".users u ON ap.user_id = u.id" . $whereSQL . "
            ORDER BY ap.last_name ASC, ap.first_name ASC
        ";
        
        $stmt = $contentDb->prepare($sql);
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
                   studiengang, study_program, semester, angestrebter_abschluss, 
                   degree, graduation_year, about_me,
                   image_path, last_verified_at, last_reminder_sent_at, created_at, updated_at
            FROM alumni_profiles 
            WHERE last_verified_at < DATE_SUB(NOW(), INTERVAL ? MONTH)
              AND (last_reminder_sent_at IS NULL OR last_reminder_sent_at < DATE_SUB(NOW(), INTERVAL ? MONTH))
            ORDER BY last_verified_at ASC
        ");
        $stmt->execute([$months, $months]);
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
    
    /**
     * Mark that a reminder email was sent to this user
     * 
     * @param int $userId The user ID
     * @return bool True on success
     */
    public static function markReminderSent(int $userId): bool {
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            UPDATE alumni_profiles 
            SET last_reminder_sent_at = NOW() 
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
}
