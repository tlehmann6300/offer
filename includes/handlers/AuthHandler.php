<?php
/**
 * Authentication Handler
 * Manages user authentication, sessions, and 2FA
 */

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth.php';

class AuthHandler {
    
    /**
     * Start secure session
     */
    public static function startSession() {
        // Set timezone at the very beginning
        date_default_timezone_set('Europe/Berlin');
        
        // Use the centralized init_session() function for secure session initialization
        init_session();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
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
        if (!password_verify($password, $user['password'])) {
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
        // head can edit inventory (level 2)
        // board roles and alumni_board have full board access (level 3)
        // Note: 'admin', 'board', and 'manager' kept for backward compatibility with legacy code paths.
        // 'manager' is DEPRECATED in favor of 'head' but kept for existing users
        // 'board' is a placeholder level 3 role used for permission checks
        // 'admin' is DEPRECATED and not assignable to new users
        $roleHierarchy = [
            'candidate' => 1,
            'alumni' => 1, 
            'member' => 1,
            'honorary_member' => 1,
            'manager' => 2,  // DEPRECATED: Use 'head' instead. Kept for existing users.
            'head' => 2,
            'alumni_board' => 3,
            'alumni_auditor' => 3,  // Same level as alumni_board
            'board_finance' => 3,
            'board_internal' => 3,
            'board_external' => 3,
            'board' => 3,  // DEPRECATED: Placeholder for backward compatibility checks
            'admin' => 3  // DEPRECATED: Keep for backward compatibility only. Not assignable to new users.
        ];
        $userRole = $_SESSION['user_role'];
        
        // Check if user role exists in hierarchy
        if (!isset($roleHierarchy[$userRole]) || !isset($roleHierarchy[$requiredRole])) {
            return false;
        }
        
        return $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
    }

    /**
     * Check if user has specific role (exact match, not hierarchical)
     * Special case: 'admin' and 'board' role checks return true for board users
     * 
     * @param string $role Required role
     * @return bool True if user has exact role
     */
    public static function hasRole($role) {
        self::startSession();
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        
        // Special case: 'admin' maps to board_finance for backward compatibility
        if ($role === 'admin') {
            return Auth::isAdmin();
        }
        
        // Special case: 'board' maps to any board role for backward compatibility
        if ($role === 'board') {
            return Auth::isBoard();
        }
        
        return $userRole === $role;
    }

    /**
     * Check if user is admin (general system access for Logs, Stats, User Management)
     * 
     * @return bool True if user has any board role (board_finance, board_internal, board_external)
     */
    public static function isAdmin() {
        return self::isBoard();
    }

    /**
     * Require admin privileges (any board role)
     * Redirects to login if not authorized
     */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            $loginUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/auth/login.php' : '/pages/auth/login.php';
            header('Location: ' . $loginUrl);
            exit;
        }
    }
    
    /**
     * Check if user is a board member (any board role)
     * 
     * @return bool True if user has any board role
     */
    public static function isBoard() {
        self::startSession();
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        return in_array($userRole, Auth::BOARD_ROLES);
    }
    
    /**
     * Check if user can manage invoices
     * 
     * @return bool True if user is board_finance
     */
    public static function canManageInvoices() {
        self::startSession();
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? '';
        return $userRole === 'board_finance';
    }
    
    /**
     * Check if user can manage users
     * 
     * @return bool True if user has any board role
     */
    public static function canManageUsers() {
        return self::isBoard();
    }
    
    /**
     * Check if user can see system stats (Logs, Stats, Dashboard)
     * 
     * @return bool True if user has any board role (board_finance, board_internal, board_external)
     */
    public static function canSeeSystemStats() {
        return self::isBoard();
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
    public static function generateInvitationToken($email, $role, $createdBy, $validityHours = 168) {
        $db = Database::getUserDB();
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ($validityHours * 60 * 60)); // Use provided validity hours
        
        $stmt = $db->prepare("INSERT INTO invitation_tokens (token, email, role, created_by, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$token, $email, $role, $createdBy, $expiresAt]);
        
        self::logSystemAction($createdBy, 'invitation_created', 'invitation', $db->lastInsertId(), "Invitation sent to $email with validity of $validityHours hours");
        
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

    /**
     * Initiate Microsoft Entra ID OAuth login
     */
    public static function initiateMicrosoftLogin() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        self::startSession();
        
        // Load credentials from configuration constants
        $clientId = defined('AZURE_CLIENT_ID') ? AZURE_CLIENT_ID : '';
        $clientSecret = defined('AZURE_CLIENT_SECRET') ? AZURE_CLIENT_SECRET : '';
        $redirectUri = defined('AZURE_REDIRECT_URI') ? AZURE_REDIRECT_URI : '';
        $tenantId = defined('AZURE_TENANT_ID') ? AZURE_TENANT_ID : '';
        
        // Validate required environment variables
        if (empty($clientId) || empty($clientSecret) || empty($redirectUri) || empty($tenantId)) {
            throw new Exception('Missing Azure OAuth configuration');
        }
        
        // Initialize Azure OAuth provider
        $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri'  => $redirectUri,
            'tenant'       => $tenantId
        ]);
        
        // Set scope
        $provider->scope = 'openid profile email offline_access User.Read';
        
        // Generate authorization URL
        $authorizationUrl = $provider->getAuthorizationUrl();
        
        // Store state in session for CSRF protection
        $_SESSION['oauth2state'] = $provider->getState();
        
        // Redirect to authorization URL
        header('Location: ' . $authorizationUrl);
        exit;
    }

    /**
     * Handle Microsoft Entra ID OAuth callback
     */
    public static function handleMicrosoftCallback() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        self::startSession();
        
        // Validate state for CSRF protection
        if (!isset($_GET['state']) || !isset($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
            unset($_SESSION['oauth2state']);
            throw new Exception('Invalid state parameter');
        }
        
        // Clear state
        unset($_SESSION['oauth2state']);
        
        // Check for error
        if (isset($_GET['error'])) {
            throw new Exception('OAuth error: ' . ($_GET['error_description'] ?? $_GET['error']));
        }
        
        // Check for authorization code
        if (!isset($_GET['code'])) {
            throw new Exception('No authorization code received');
        }
        
        // Load credentials from configuration constants
        $clientId = defined('AZURE_CLIENT_ID') ? AZURE_CLIENT_ID : '';
        $clientSecret = defined('AZURE_CLIENT_SECRET') ? AZURE_CLIENT_SECRET : '';
        $redirectUri = defined('AZURE_REDIRECT_URI') ? AZURE_REDIRECT_URI : '';
        $tenantId = defined('AZURE_TENANT_ID') ? AZURE_TENANT_ID : '';
        
        // Validate required environment variables
        if (empty($clientId) || empty($clientSecret) || empty($redirectUri) || empty($tenantId)) {
            throw new Exception('Missing Azure OAuth configuration');
        }
        
        // Initialize Azure OAuth provider
        $provider = new \TheNetworg\OAuth2\Client\Provider\Azure([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri'  => $redirectUri,
            'tenant'       => $tenantId
        ]);
        
        try {
            // Get access token using authorization code
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);
            
            // Get user details
            $user = $provider->get('me', $token);
            
            // Get user claims (including roles)
            $claims = $user->getClaims();
            $azureRoles = $claims['roles'] ?? [];
            
            // Define role mapping from Azure roles (string) to internal role names (string)
            // Azure roles should not contain umlauts for technical compatibility
            $roleMapping = [
                'anwaerter' => 'candidate',
                'mitglied' => 'member',
                'ressortleiter' => 'head',
                'vorstand_finanzen' => 'board_finance',
                'vorstand_intern' => 'board_internal',
                'vorstand_extern' => 'board_external',
                'alumni' => 'alumni',
                'alumni_vorstand' => 'alumni_board',
                'alumni_finanz' => 'alumni_auditor',
                'ehrenmitglied' => 'honorary_member'
            ];
            
            // Define role hierarchy for priority selection (higher value = higher priority)
            $roleHierarchy = [
                'candidate' => 1,
                'member' => 2,
                'head' => 3,
                'alumni' => 4,
                'honorary_member' => 5,
                'board_finance' => 6,
                'board_internal' => 7,
                'board_external' => 8,
                'alumni_board' => 9,
                'alumni_auditor' => 10
            ];
            
            // Find the role with the highest priority
            $highestPriority = 0;
            $selectedRole = 'member'; // Default to member if no valid role found
            
            foreach ($azureRoles as $azureRole) {
                if (isset($roleMapping[$azureRole])) {
                    $internalRole = $roleMapping[$azureRole];
                    $priority = $roleHierarchy[$internalRole] ?? 0;
                    
                    if ($priority > $highestPriority) {
                        $highestPriority = $priority;
                        $selectedRole = $internalRole;
                    }
                }
            }
            
            $roleName = $selectedRole;
            
            // Get user email
            $email = $user->getEmail();
            
            // Check if user exists in database
            $db = Database::getUserDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Update existing user
                $userId = $existingUser['id'];
                
                // Update last login
                $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$userId]);
            } else {
                // Create new user without password (OAuth login only)
                $stmt = $db->prepare("INSERT INTO users (email, password, role, is_alumni_validated, profile_complete) VALUES (?, ?, ?, ?, ?)");
                $isAlumniValidated = ($roleName === 'alumni') ? 0 : 1;
                $profileComplete = 0;
                // Use a random password hash since user will login via OAuth
                $randomPassword = password_hash(bin2hex(random_bytes(32)), HASH_ALGO);
                $stmt->execute([$email, $randomPassword, $roleName, $isAlumniValidated, $profileComplete]);
                $userId = $db->lastInsertId();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $roleName;
            $_SESSION['authenticated'] = true;
            $_SESSION['last_activity'] = time();
            
            // Log successful login
            self::logSystemAction($userId, 'login_success_microsoft', 'user', $userId, 'Successful Microsoft Entra ID login');
            
            // Redirect to dashboard
            $dashboardUrl = (defined('BASE_URL') && BASE_URL) ? BASE_URL . '/pages/dashboard/index.php' : '/pages/dashboard/index.php';
            header('Location: ' . $dashboardUrl);
            exit;
            
        } catch (Exception $e) {
            self::logSystemAction(null, 'login_failed_microsoft', 'user', null, 'Microsoft login error: ' . $e->getMessage());
            throw new Exception('Failed to authenticate with Microsoft: ' . $e->getMessage());
        }
    }
}
