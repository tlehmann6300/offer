<?php
/**
 * Project Model
 * Manages project data and operations with security controls
 */

class Project {
    
    /**
     * Create a new project
     */
    public static function create($data) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            INSERT INTO projects (
                title, 
                description, 
                client_name, 
                client_contact_details, 
                priority, 
                status, 
                start_date, 
                end_date, 
                image_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['title'] ?? '',
            $data['description'] ?? null,
            $data['client_name'] ?? null,
            $data['client_contact_details'] ?? null,
            $data['priority'] ?? 'medium',
            $data['status'] ?? 'draft',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['image_path'] ?? null
        ]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Update an existing project
     */
    public static function update($id, $data) {
        $db = Database::getContentDB();
        $fields = [];
        $values = [];
        
        $allowedFields = [
            'title', 'description', 'client_name', 'client_contact_details',
            'priority', 'status', 'start_date', 'end_date', 'image_path'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get all projects
     */
    public static function getAll() {
        $db = Database::getContentDB();
        $stmt = $db->query("SELECT * FROM projects ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get project by ID
     */
    public static function getById($id) {
        $db = Database::getContentDB();
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Filter sensitive data based on user role and project assignment
     * 
     * Removes client_name and client_contact_details if:
     * - User role is not 'board' or 'manager' AND
     * - User is not assigned to the project
     * - Alumni never see client data
     * 
     * @param array $project Project data
     * @param string $userRole User role (board, manager, member, alumni)
     * @param int $userId User ID
     * @return array Filtered project data
     */
    public static function filterSensitiveData($project, $userRole, $userId) {
        if (!$project) {
            return $project;
        }
        
        // Alumni should never see client data
        if ($userRole === 'alumni') {
            unset($project['client_name']);
            unset($project['client_contact_details']);
            return $project;
        }
        
        // Board and manager can always see client data
        if ($userRole === 'board' || $userRole === 'manager') {
            return $project;
        }
        
        // Check if user is assigned to this project
        $db = Database::getContentDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM project_assignments 
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([$project['id'], $userId]);
        $result = $stmt->fetch();
        
        // If user is assigned to the project, they can see client data
        if ($result && $result['count'] > 0) {
            return $project;
        }
        
        // Otherwise, remove sensitive data
        unset($project['client_name']);
        unset($project['client_contact_details']);
        
        return $project;
    }
    
    /**
     * Apply for a project
     * 
     * @param int $projectId Project ID
     * @param int $userId User ID
     * @param array $data Application data (motivation, experience_count)
     * @return int Application ID
     */
    public static function apply($projectId, $userId, $data) {
        $db = Database::getContentDB();
        
        // Check if application already exists
        $stmt = $db->prepare("
            SELECT id FROM project_applications 
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([$projectId, $userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            throw new Exception("Application already exists for this project");
        }
        
        $stmt = $db->prepare("
            INSERT INTO project_applications (
                project_id, 
                user_id, 
                motivation, 
                experience_count, 
                status
            ) VALUES (?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $projectId,
            $userId,
            $data['motivation'] ?? null,
            $data['experience_count'] ?? 0
        ]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Assign a member to a project
     * 
     * @param int $projectId Project ID
     * @param int $userId User ID
     * @param string $role Assignment role (lead, member)
     * @return int Assignment ID
     */
    public static function assignMember($projectId, $userId, $role = 'member') {
        $db = Database::getContentDB();
        
        // Check if assignment already exists
        $stmt = $db->prepare("
            SELECT id FROM project_assignments 
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->execute([$projectId, $userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing assignment
            $stmt = $db->prepare("
                UPDATE project_assignments 
                SET role = ? 
                WHERE project_id = ? AND user_id = ?
            ");
            $stmt->execute([$role, $projectId, $userId]);
            return $existing['id'];
        }
        
        // Create new assignment
        $stmt = $db->prepare("
            INSERT INTO project_assignments (
                project_id, 
                user_id, 
                role
            ) VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $projectId,
            $userId,
            $role
        ]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Get applications for a project
     * Only accessible to admins and board members
     * 
     * @param int $projectId Project ID
     * @return array Applications with user information
     */
    public static function getApplications($projectId) {
        $db = Database::getContentDB();
        
        $stmt = $db->prepare("
            SELECT 
                pa.id,
                pa.project_id,
                pa.user_id,
                pa.motivation,
                pa.experience_count,
                pa.status,
                pa.created_at
            FROM project_applications pa
            WHERE pa.project_id = ?
            ORDER BY pa.created_at DESC
        ");
        
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }
}
