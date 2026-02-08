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
 * Generate URL relative to document root using BASE_URL
 * Uses BASE_URL constant for robust URL generation regardless of subdirectory depth
 */
function url($path) {
    // Remove trailing slashes from BASE_URL
    $baseUrl = rtrim(BASE_URL, '/');
    
    // Remove leading slashes from path
    $path = ltrim($path, '/');
    
    // Combine with exactly one slash
    return $baseUrl . '/' . $path;
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

/**
 * Generate asset path with BASE_URL
 * Ensures no double slash by using rtrim on BASE_URL
 * This is an alias for asset_url() for convenience
 */
function asset($path) {
    return asset_url($path);
}

/**
 * Translate role from English to German
 * 
 * @param string $role Role identifier
 * @return string German translation of the role
 */
function translateRole($role) {
    $roleTranslations = [
        'board' => 'Vorstand',
        'head' => 'Vorstandsvorsitzender',
        'member' => 'Mitglied',
        'alumni' => 'Alumni',
        'candidate' => 'Anwärter',
        'alumni_board' => 'Alumni-Vorstand',
        'manager' => 'Ressortleiter',
        'admin' => 'Administrator'
    ];
    
    return $roleTranslations[$role] ?? ucfirst($role);
}
