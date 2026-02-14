<?php
/**
 * Security Headers Configuration
 * 
 * This file sets HTTP response headers to harden the application's security posture.
 * Headers are only set if they haven't been sent already to avoid conflicts.
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
    
    // Content-Security-Policy: Controls which resources can be loaded
    // This policy is based on the external resources used in the application:
    // - Tailwind CSS CDN (cdn.tailwindcss.com)
    // - Font Awesome CDN (cdnjs.cloudflare.com)
    // - Google Fonts (fonts.googleapis.com, fonts.gstatic.com)
    // NOTE: 'unsafe-inline' is required for:
    //   - Inline Tailwind configuration script in layout templates
    //   - Inline styles in various components
    // This is a tradeoff between CSP strictness and development convenience.
    // For better security, consider migrating to nonce-based CSP or external files.
    if (!header_sent_check('Content-Security-Policy')) {
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdnjs.cloudflare.com fonts.googleapis.com",
            "font-src 'self' cdnjs.cloudflare.com fonts.gstatic.com",
            "img-src 'self' data: blob:",
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
}
