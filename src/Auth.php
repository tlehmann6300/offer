<?php
/**
 * Auth Class
 * Complete authentication handler with session management and auto-logout
 */

require_once __DIR__ . '/Database.php';

class Auth {
    
    /**
     * All valid role types in the system
     */
    const VALID_ROLES = [
        'candidate',
        'member',
        'head',
        'alumni',
        'alumni_board',
        'board',
        'vorstand_intern',
        'vorstand_extern',
        'vorstand_finanzen_recht',
        'honorary_member'
    ];
    
    /**
     * Board role types (all variants)
     */
    const BOARD_ROLES = [
        'board',
        'vorstand_intern',
        'vorstand_extern',
        'vorstand_finanzen_recht'
    ];
    

    /**
     * Check if user is authenticated and handle session timeout
     * 
     * @return bool True if authenticated
     */
    public static function check() {
        // Set timezone
        date_default_timezone_set('Europe/Berlin');
        
        // Initialize session with secure parameters
        init_session();
        
        // Check if user is authenticated
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return false;
        }
        
        // Check for session timeout (30 minutes = 1800 seconds)
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            
            // If inactive for more than 30 minutes
            if ($inactiveTime > 1800) {
                // Destroy session
                session_unset();
                session_destroy();
                
                // Redirect to login with timeout parameter
                $loginUrl = '/pages/auth/login.php?timeout=1';
                if (defined('BASE_URL') && BASE_URL) {
                    $loginUrl = BASE_URL . $loginUrl;
                }
                header('Location: ' . $loginUrl);
                exit;
            }
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Verify user credentials (email and password)
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|false User array on success, false on failure
     */
    public static function verifyCredentials($email, $password) {
        $db = Database::getUserDB();
        
        // Find user by email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Check if account is permanently locked
        if (isset($user['is_locked_permanently']) && $user['is_locked_permanently']) {
            return false;
        }
        
        // Check if account is temporarily locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return false;
        }
        
        // Check if password field exists and is valid
        if (!isset($user['password']) || !is_string($user['password'])) {
            error_log("Database error: password field missing or invalid for user ID: " . $user['id']);
            return false;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
            $lockedUntil = null;
            $isPermanentlyLocked = 0;
            
            // Lock account for 30 minutes after 5 failed attempts
            if ($failedAttempts == 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + (30 * 60)); // Lock for 30 minutes
            }
            
            // Permanently lock account after 8 failed attempts
            if ($failedAttempts >= 8) {
                $isPermanentlyLocked = 1;
                $lockedUntil = null; // Clear temporary lock when applying permanent lock
            }
            
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ?, is_locked_permanently = ? WHERE id = ?");
            $stmt->execute([$failedAttempts, $lockedUntil, $isPermanentlyLocked, $user['id']]);
            
            return false;
        }
        
        return $user;
    }
    
    /**
     * Create session for authenticated user
     * 
     * @param array $user User data array
     * @return bool True on success
     */
    public static function createSession($user) {
        $db = Database::getUserDB();
        
        // Reset failed attempts and update last login
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Initialize session with secure parameters
        init_session();
        
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        
        // Set 2FA nudge if 2FA is not enabled
        if (!$user['tfa_enabled']) {
            $_SESSION['show_2fa_nudge'] = true;
        }
        
        return true;
    }
    
    /**
     * Login user with email and password
     * 
     * @param string $email User email
     * @param string $password User password
     * @param string|null $tfaCode Optional 2FA code
     * @return array Result with 'success' and 'message' keys
     */
    public static function login($email, $password, $tfaCode = null) {
        // Verify credentials
        $user = self::verifyCredentials($email, $password);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Ungültige Anmeldedaten'];
        }
        
        // Check 2FA if enabled
        if ($user['tfa_enabled']) {
            if ($tfaCode === null) {
                return ['success' => false, 'require_2fa' => true, 'user_id' => $user['id']];
            }
            
            // Verify 2FA code
            require_once __DIR__ . '/../includes/handlers/GoogleAuthenticator.php';
            $ga = new PHPGangsta_GoogleAuthenticator();
            
            if (!$ga->verifyCode($user['tfa_secret'], $tfaCode, 2)) {
                return ['success' => false, 'message' => 'Ungültiger 2FA-Code'];
            }
        }
        
        // Create session
        self::createSession($user);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Logout current user
     */
    public static function logout() {
        // Initialize session with secure parameters if not already started
        init_session();
        
        // Clear all session data
        session_unset();
        session_destroy();
    }
    
    /**
     * Check if user has specific role(s)
     * 
     * @param string|array $role Required role or array of roles
     * @return bool True if user has the role
     */
    public static function hasRole($role) {
        if (!self::check()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        // If $role is an array, check if user has any of them
        if (is_array($role)) {
            return in_array($userRole, $role);
        }
        
        // If $role is a string, check for exact match
        return $userRole === $role;
    }
    
    /**
     * Check if user has any board role
     * 
     * @return bool True if user has any board role
     */
    public static function isBoardMember() {
        if (!self::check()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        return in_array($userRole, self::BOARD_ROLES);
    }
    
    /**
     * Check if user has specific permission/role
     * 
     * @param string $role Required role
     * @return bool True if user has permission
     */
    public static function hasPermission($role) {
        if (!self::check()) {
            return false;
        }
        
        // Role hierarchy
        // Note: 'admin' kept at level 3 for backward compatibility only
        // New users cannot be assigned 'admin' role (not in VALID_ROLES)
        $roleHierarchy = [
            'candidate' => 0,
            'alumni' => 1,
            'member' => 1,
            'honorary_member' => 1,
            'head' => 2,
            'manager' => 2,
            'manage_projects' => 2,  // Permission for manager-level project access
            'alumni_board' => 3,
            'board' => 3,
            'vorstand_intern' => 3,
            'vorstand_extern' => 3,
            'vorstand_finanzen_recht' => 3,
            'admin' => 3  // Keep for backward compatibility, treat as board level
        ];
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        if (!isset($roleHierarchy[$userRole]) || !isset($roleHierarchy[$role])) {
            return false;
        }
        
        return $roleHierarchy[$userRole] >= $roleHierarchy[$role];
    }
    
    /**
     * Get current user data
     * 
     * @return array|null User data or null if not authenticated
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        $db = Database::getUserDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $result = $stmt->fetch();
        
        // If user not found in database (zombie session), destroy session and return null
        if ($result === false) {
            // Log zombie session detection for security monitoring
            error_log("Zombie session detected: User ID " . ($_SESSION['user_id'] ?? 'unknown') . 
                      " not found in database. IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            self::logout();
            return null;
        }
        
        return $result;
    }
    
    /**
     * Require specific role or redirect to login
     * 
     * @param string $role Required role
     */
    public static function requireRole($role) {
        if (!self::hasPermission($role)) {
            $loginUrl = '/pages/auth/login.php';
            if (defined('BASE_URL') && BASE_URL) {
                $loginUrl = BASE_URL . $loginUrl;
            }
            header('Location: ' . $loginUrl);
            exit;
        }
    }
    
    /**
     * Generate invitation token for new user registration
     * 
     * @param string $email User email
     * @param string $role User role
     * @param int $createdBy ID of user creating the invitation
     * @return string Generated token
     */
    public static function generateInvitationToken($email, $role, $createdBy, $validityHours = 168) {
        $db = Database::getUserDB();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ($validityHours * 60 * 60)); // Use provided validity hours
        
        $stmt = $db->prepare("INSERT INTO invitation_tokens (token, email, role, created_by, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$token, $email, $role, $createdBy, $expiresAt]);
        
        // Capture last insert ID immediately after insert
        $invitationId = $db->lastInsertId();
        
        // Log invitation creation if system_logs table exists
        try {
            $dbContent = Database::getContentDB();
            $stmt = $dbContent->prepare("INSERT INTO system_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $createdBy,
                'invitation_created',
                'invitation',
                $invitationId,
                "Invitation sent to $email with validity of $validityHours hours",
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log invitation creation: " . $e->getMessage());
        }
        
        return $token;
    }
    
    /**
     * Create new Auth instance (for compatibility)
     * 
     * @return Auth New Auth instance
     */
    public static function getInstance() {
        return new self();
    }
}
