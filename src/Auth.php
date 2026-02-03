<?php
/**
 * Auth Class
 * Complete authentication handler with session management and auto-logout
 */

require_once __DIR__ . '/Database.php';

class Auth {
    
    /**
     * Extract domain from BASE_URL for session cookie
     * 
     * @return string Domain from BASE_URL or empty string
     */
    private static function getDomainFromBaseUrl() {
        if (!defined('BASE_URL') || !BASE_URL) {
            return '';
        }
        
        $parsed = parse_url(BASE_URL);
        return $parsed['host'] ?? '';
    }
    
    /**
     * Set secure session cookie parameters
     */
    private static function setSecureSessionParams() {
        if (session_status() === PHP_SESSION_NONE) {
            // Get session lifetime from config, default to 3600 seconds (1 hour)
            $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
            
            // Get domain from BASE_URL
            $domain = self::getDomainFromBaseUrl();
            
            // Set secure cookie parameters
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }
    
    /**
     * Check if user is authenticated and handle session timeout
     * 
     * @return bool True if authenticated
     */
    public static function check() {
        // Set timezone
        date_default_timezone_set('Europe/Berlin');
        
        // Set secure session cookie parameters before starting session
        self::setSecureSessionParams();
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
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
     * Login user with email and password
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array Result with 'success' and 'message' keys
     */
    public static function login($email, $password) {
        $db = Database::getUserDB();
        
        // Find user by email
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
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
            $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
            $lockedUntil = null;
            
            // Lock account after 5 failed attempts
            if ($failedAttempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + (15 * 60)); // Lock for 15 minutes
            }
            
            $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$failedAttempts, $lockedUntil, $user['id']]);
            
            return ['success' => false, 'message' => 'Ungültige Anmeldedaten'];
        }
        
        // Reset failed attempts and update last login
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Set secure session cookie parameters before starting session
        self::setSecureSessionParams();
        
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Logout current user
     */
    public static function logout() {
        // Set secure session cookie parameters before starting session
        self::setSecureSessionParams();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear all session data
        session_unset();
        session_destroy();
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
        $roleHierarchy = [
            'alumni' => 1,
            'member' => 1,
            'manager' => 2,
            'alumni_board' => 3,
            'board' => 3,
            'admin' => 4
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
        
        return $stmt->fetch();
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
     * Create new Auth instance (for compatibility)
     * 
     * @return Auth New Auth instance
     */
    public static function getInstance() {
        return new self();
    }
}
