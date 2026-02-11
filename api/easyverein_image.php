<?php
/**
 * EasyVerein Image Proxy
 * Securely fetches images from EasyVerein API with authorization
 */

require_once __DIR__ . '/../config/config.php';

// Set headers for security
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check if URL parameter is provided
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    outputPlaceholder();
    exit;
}

$imageUrl = $_GET['url'];

// Security: Validate that the URL contains 'easyverein.com'
if (strpos($imageUrl, 'easyverein.com') === false) {
    http_response_code(403);
    outputPlaceholder();
    exit;
}

// Get API token from config
$apiToken = defined('EASYVEREIN_API_TOKEN') ? EASYVEREIN_API_TOKEN : '';

if (empty($apiToken)) {
    error_log('EasyVerein Image Proxy: API token not configured');
    http_response_code(500);
    outputPlaceholder();
    exit;
}

try {
    // Fetch the image using cURL with authorization
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiToken
    ]);
    
    // Execute request
    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Check for errors
    if ($imageData === false) {
        error_log('EasyVerein Image Proxy - cURL error: ' . $curlError);
        http_response_code(500);
        outputPlaceholder();
        exit;
    }
    
    // Check HTTP status
    if ($httpCode !== 200) {
        error_log('EasyVerein Image Proxy - HTTP ' . $httpCode . ' for URL: ' . $imageUrl);
        http_response_code($httpCode);
        outputPlaceholder();
        exit;
    }
    
    // Validate content type is an image
    if (!$contentType || strpos($contentType, 'image/') !== 0) {
        error_log('EasyVerein Image Proxy - Invalid content type: ' . $contentType);
        http_response_code(400);
        outputPlaceholder();
        exit;
    }
    
    // Set the correct Content-Type header
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
    
    // Output the image data
    echo $imageData;
    
} catch (Exception $e) {
    error_log('EasyVerein Image Proxy - Exception: ' . $e->getMessage());
    http_response_code(500);
    outputPlaceholder();
}

/**
 * Output a simple placeholder image (1x1 transparent PNG)
 */
function outputPlaceholder() {
    header('Content-Type: image/png');
    // 1x1 transparent PNG
    $placeholder = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    echo $placeholder;
}
