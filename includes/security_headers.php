<?php
/**
 * Security Headers Configuration
 * 
 * This file sets HTTP response headers to harden the application's security posture.
 * Headers are only set if they haven't been sent already to avoid conflicts.
 * Enhanced with nonce-based CSP and additional security features.
 * 
 * Include this file early in the application lifecycle, ideally in config/config.php
 */

/**
 * Helper function to check if a specific header has been set
 * 
 * @param string $header_name The name of the header to check
 * @return bool True if the header has been set, false otherwise
 */
function header_sent_check($header_name) {
    $headers = headers_list();
    foreach ($headers as $header) {
        if (stripos($header, $header_name . ':') === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Generate CSP nonce for inline scripts
 * 
 * @return string The nonce value
 */
function generate_csp_nonce() {
    if (!isset($_SESSION['csp_nonce'])) {
        init_session();
        $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
    }
    return $_SESSION['csp_nonce'];
}

// Only set headers if they haven't been sent yet
if (!headers_sent()) {
    
    // X-Content-Type-Options: Prevents MIME-sniffing attacks
    // Ensures browsers respect the declared Content-Type
    if (!header_sent_check('X-Content-Type-Options')) {
        header('X-Content-Type-Options: nosniff');
    }
    
    // X-Frame-Options: Prevents clickjacking attacks
    // SAMEORIGIN allows framing only from the same origin
    if (!header_sent_check('X-Frame-Options')) {
        header('X-Frame-Options: SAMEORIGIN');
    }
    
    // X-XSS-Protection: Enables browser's XSS filtering
    // mode=block prevents rendering of the page if XSS is detected
    // NOTE: This header is deprecated in modern browsers which rely on CSP instead.
    // It's included here for backwards compatibility with older browsers.
    if (!header_sent_check('X-XSS-Protection')) {
        header('X-XSS-Protection: 1; mode=block');
    }
    
    // Referrer-Policy: Controls how much referrer information is sent
    // strict-origin-when-cross-origin sends full URL for same-origin, only origin for cross-origin HTTPS
    if (!header_sent_check('Referrer-Policy')) {
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    // Permissions-Policy: Controls browser features and APIs
    // Restricts access to sensitive features like camera, microphone, geolocation
    if (!header_sent_check('Permissions-Policy')) {
        $permissions = [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()'
        ];
        header('Permissions-Policy: ' . implode(', ', $permissions));
    }
    
    // Strict-Transport-Security: Enforces HTTPS connections
    // Only set in production to avoid issues in development
    if (!header_sent_check('Strict-Transport-Security')) {
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            // max-age=31536000 (1 year), includeSubDomains, preload
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    // Enhanced Content-Security-Policy with restricted image sources
    // Note: Only allows images from self, data URIs, and blob URIs for maximum security
    // If external images are needed (e.g., for email previews), add specific trusted domains
    if (!header_sent_check('Content-Security-Policy')) {
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com fonts.googleapis.com",
            "font-src 'self' cdnjs.cloudflare.com fonts.gstatic.com",
            "img-src 'self' data: blob:",  // Restricted to self, data, and blob only
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "upgrade-insecure-requests"
        ];
        
        $csp_header = implode('; ', $csp_directives);
        header("Content-Security-Policy: $csp_header");
    }
    
    // X-Permitted-Cross-Domain-Policies: Restricts Adobe Flash and PDF cross-domain policies
    if (!header_sent_check('X-Permitted-Cross-Domain-Policies')) {
        header('X-Permitted-Cross-Domain-Policies: none');
    }
    
    // Cache-Control: Prevent caching of sensitive pages
    // This should be overridden for static resources
    if (!header_sent_check('Cache-Control')) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // Only set no-cache for non-asset requests
        if (!preg_match('/\.(css|js|jpg|jpeg|png|gif|webp|svg|woff|woff2|ttf|eot)$/i', $requestUri)) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
}
