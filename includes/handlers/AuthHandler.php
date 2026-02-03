<?php
/**
 * Authentication Handler
 * Manages user authentication, sessions, and 2FA
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/User.php';

class AuthHandler {
    
    /**
     * Start secure session
     */
    public static function startSession() {
        // Set timezone at the very beginning
        date_default_timezone_set('Europe/Berlin');
        
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 1); // Set to 1 if using HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            session_name(SESSION_NAME);
            session_start();
            
            // Regenerate session ID periodically to prevent session fixation
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
        
        // Check for session timeout (30 minutes of inactivity)
        self::checkSessionTimeout();
    }
    
    /**
     * Check if session has timed out due to inactivity
     */
    private static function checkSessionTimeout() {
        // Skip timeout check if user is not authenticated
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            return;
        }
        
        // Check if last_activity is set
        if (isset($_SESSION['last_activity'])) {
            // Calculate time difference
            $inactiveTime = time() - $_SESSION['last_activity'];
            
            // If inactive for more than 30 minutes (1800 seconds)
            if ($inactiveTime > 1800) {
                // Destroy the session
                session_unset();
                session_destroy();
                
                // Redirect to login page with timeout parameter
                // Use BASE_URL for portability across environments
                $loginUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/auth/login.php?timeout=1' : '/pages/auth/login.php?timeout=1';
                header('Location: ' . $loginUrl);
                exit;
            }
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    }

    /**
     * Login user
     */
    public static function login($email, $password, $tfaCode = null) {
        $db = Database::getUserDB();
        
        // Check if user is locked out
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            self::logSystemAction(null, 'login_failed', 'user', null, 'User not found: ' . $email);
            return ['success' => false, 'message' => 'Ungültige Anmeldedaten'];
        }
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'message' => "Konto gesperrt. Versuchen Sie es in $remainingTime Minuten erneut."];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            // Increment failed attempts
            $failedAttempts = $user['failed_login_attempts'] + 1;
            $lockedUntil = null;
            
            if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            }
            
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$failedAttempts, $lockedUntil, $user['id']]);
            
            self::logSystemAction($user['id'], 'login_failed', 'user', $user['id'], 'Invalid password');
            return ['success' => false, 'message' => 'Ungültige Anmeldedaten'];
        }
        
        // Check 2FA if enabled
        if ($user['tfa_enabled']) {
            if ($tfaCode === null) {
                return ['success' => false, 'require_2fa' => true, 'user_id' => $user['id']];
            }
            
            require_once __DIR__ . '/GoogleAuthenticator.php';
            $ga = new PHPGangsta_GoogleAuthenticator();
            
            if (!$ga->verifyCode($user['tfa_secret'], $tfaCode, 2)) {
                self::logSystemAction($user['id'], 'login_2fa_failed', 'user', $user['id'], 'Invalid 2FA code');
                return ['success' => false, 'message' => 'Ungültiger 2FA-Code'];
            }
        }
        
        // Reset failed attempts and update last login
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Set session variables
        self::startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time(); // Initialize activity timestamp
        
        self::logSystemAction($user['id'], 'login_success', 'user', $user['id'], 'Successful login');
        
        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout user
     */
    public static function logout() {
        self::startSession();
        
        if (isset($_SESSION['user_id'])) {
            self::logSystemAction($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
        }
        
        session_destroy();
        session_unset();
    }

    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        self::startSession();
        return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    }

    /**
     * Get current user
     */
    public static function getCurrentUser() {
        self::startSession();
        if (self::isAuthenticated()) {
            return User::getById($_SESSION['user_id']);
        }
        return null;
    }

    /**
     * Check if user has permission
     */
    public static function hasPermission($requiredRole) {
        self::startSession();
        if (!self::isAuthenticated()) {
            return false;
        }
        
        // Role hierarchy: alumni and member have read-only access (level 1)
        // manager can edit inventory (level 2)
        // board and alumni_board have full board access (level 3)
        // admin has full system access (level 4)
        $roleHierarchy = [
            'alumni' => 1, 
            'member' => 1, 
            'manager' => 2, 
            'alumni_board' => 3,
            'board' => 3, 
            'admin' => 4
        ];
        $userRole = $_SESSION['user_role'];
        
        // Check if user role exists in hierarchy
        if (!isset($roleHierarchy[$userRole]) || !isset($roleHierarchy[$requiredRole])) {
            return false;
        }
        
        return $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
    }

    /**
     * Check if alumni user is validated
     * Alumni users need board approval before accessing internal alumni network data
     */
    public static function isAlumniValidated() {
        self::startSession();
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $user = self::getCurrentUser();
        if (!$user || $user['role'] !== 'alumni') {
            return true; // Non-alumni users are always "validated"
        }
        
        return $user['is_alumni_validated'] == 1;
    }

    /**
     * Generate invitation token
     */
    public static function generateInvitationToken($email, $role, $createdBy) {
        $db = Database::getUserDB();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (7 * 24 * 60 * 60)); // 7 days
        
        $stmt = $db->prepare("INSERT INTO invitation_tokens (token, email, role, created_by, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$token, $email, $role, $createdBy, $expiresAt]);
        
        self::logSystemAction($createdBy, 'invitation_created', 'invitation', $db->lastInsertId(), "Invitation sent to $email");
        
        return $token;
    }

    /**
     * Log system action
     */
    private static function logSystemAction($userId, $action, $entityType = null, $entityId = null, $details = null) {
        try {
            $db = Database::getContentDB();
            $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Failed to log system action: " . $e->getMessage());
        }
    }
}
