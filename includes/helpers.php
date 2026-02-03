<?php
/**
 * Helper Functions
 */

/**
 * Get base URL path
 */
function getBasePath() {
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
}

/**
 * Generate URL relative to document root
 */
function url($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Get the base path from the current script location
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(dirname($scriptPath), '/') - substr_count($_SERVER['DOCUMENT_ROOT'], '/');
    
    // Build relative path
    if (strpos($scriptPath, '/pages/') !== false) {
        $depth = 2; // From pages/subdir/
    }
    
    $basePath = str_repeat('../', $depth);
    return $basePath . $path;
}

/**
 * Redirect helper
 */
function redirect($path, $absolute = false) {
    if ($absolute) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return number_format($amount, 2, ',', '.') . ' €';
}

/**
 * Format date
 */
function formatDate($date, $format = 'd.m.Y') {
    if (empty($date)) return '-';
    return date($format, is_numeric($date) ? $date : strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($date, $format = 'd.m.Y H:i') {
    if (empty($date)) return '-';
    return date($format, is_numeric($date) ? $date : strtotime($date));
}

/**
 * Escape HTML
 */
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Check if current page is active
 */
function isActive($page) {
    return strpos($_SERVER['REQUEST_URI'], $page) !== false ? 'active' : '';
}

/**
 * Generate asset URL with BASE_URL
 * Ensures exactly one slash between BASE_URL and path
 */
function asset_url($path) {
    // Remove trailing slashes from BASE_URL
    $baseUrl = rtrim(BASE_URL, '/');
    
    // Remove leading slashes from path
    $path = ltrim($path, '/');
    
    // Combine with exactly one slash
    return $baseUrl . '/' . $path;
}
