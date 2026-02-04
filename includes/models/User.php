<?php
/**
 * User Model
 * Manages user data and operations
 */

class User {
    
    /**
     * Get user by ID
     */
    public static function getById($id) {
        $db = Database::getUserDB();
        $stmt = $db->prepare("SELECT id, email, role, tfa_enabled, is_alumni_validated, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get user by email
     */
    public static function getByEmail($email) {
        $db = Database::getUserDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Create new user
     */
    public static function create($email, $password, $role = 'member') {
        $db = Database::getUserDB();
        $passwordHash = password_hash($password, HASH_ALGO);
        
        // Alumni users need manual board approval, so is_alumni_validated is set to FALSE (0)
        // Non-alumni users don't require validation, so it's set to TRUE (1) by default
        // This allows the isAlumniValidated() check to work correctly for all users
        $isAlumniValidated = ($role === 'alumni') ? 0 : 1;
        
        $stmt = $db->prepare("INSERT INTO users (email, password_hash, role, is_alumni_validated) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $passwordHash, $role, $isAlumniValidated]);
        
        return $db->lastInsertId();
    }

    /**
     * Update user
     */
    public static function update($id, $data) {
        $db = Database::getUserDB();
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete user
     */
    public static function delete($id) {
        $db = Database::getUserDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get all users
     */
    public static function getAll($role = null) {
        $db = Database::getUserDB();
        
        if ($role) {
            $stmt = $db->prepare("SELECT id, email, role, tfa_enabled, is_alumni_validated, last_login, created_at FROM users WHERE role = ? ORDER BY created_at DESC");
            $stmt->execute([$role]);
        } else {
            $stmt = $db->query("SELECT id, email, role, tfa_enabled, is_alumni_validated, last_login, created_at FROM users ORDER BY created_at DESC");
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Enable 2FA for user
     */
    public static function enable2FA($userId, $secret) {
        $db = Database::getUserDB();
        $stmt = $db->prepare("UPDATE users SET tfa_secret = ?, tfa_enabled = 1 WHERE id = ?");
        return $stmt->execute([$secret, $userId]);
    }

    /**
     * Disable 2FA for user
     */
    public static function disable2FA($userId) {
        $db = Database::getUserDB();
        $stmt = $db->prepare("UPDATE users SET tfa_secret = NULL, tfa_enabled = 0 WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Change password
     */
    public static function changePassword($userId, $newPassword) {
        $db = Database::getUserDB();
        $passwordHash = password_hash($newPassword, HASH_ALGO);
        
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$passwordHash, $userId]);
    }

    /**
     * Update user email
     * @param int $userId The ID of the user whose email should be updated
     * @param string $newEmail The new email address
     * @return bool Returns true on success
     * @throws Exception If the email is already in use by another user or invalid
     */
    public static function updateEmail($userId, $newEmail) {
        $db = Database::getUserDB();
        
        // Validate email format
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Ungültige E-Mail-Adresse');
        }
        
        // Check if email is already used by another user
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$newEmail, $userId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            throw new Exception('E-Mail bereits vergeben');
        }
        
        // Update the email
        $stmt = $db->prepare("UPDATE users SET email = ? WHERE id = ?");
        $result = $stmt->execute([$newEmail, $userId]);
        
        // Check if the update actually affected a row
        if ($result && $stmt->rowCount() > 0) {
            return true;
        }
        
        // If no rows were affected, the user ID doesn't exist
        throw new Exception('Benutzer nicht gefunden');
    }
}
