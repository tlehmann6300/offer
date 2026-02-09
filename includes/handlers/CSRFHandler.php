<?php
/**
 * CSRF Handler
 * Manages CSRF token generation and verification for form security
 */

class CSRFHandler {
    
    /**
     * Generate and return CSRF token
     * If token doesn't exist in session, creates a new one
     * 
     * @return string The CSRF token
     */
    public static function getToken() {
        // Ensure session is started with secure parameters
        init_session();
        
        // Generate token if it doesn't exist
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     * Compares provided token with session token
     * Dies with error message if verification fails
     * 
     * @param string $token The token to verify
     * @return void
     */
    public static function verifyToken($token) {
        // Ensure session is started with secure parameters
        init_session();
        
        // Check if session token exists
        if (!isset($_SESSION['csrf_token'])) {
            die('CSRF validation failed');
        }
        
        // Compare tokens using hash_equals to prevent timing attacks
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            die('CSRF validation failed');
        }
    }
}
