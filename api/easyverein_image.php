<?php
require_once __DIR__ . '/../config/config.php';

// Security check: Only allow EasyVerein URLs
$url = $_GET['url'] ?? '';
if (empty($url) || strpos($url, 'easyverein.com') === false) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Get API Token from config or env
$token = defined('EASYVEREIN_API_TOKEN') ? EASYVEREIN_API_TOKEN : ($_ENV['EASYVEREIN_API_TOKEN'] ?? '');

// Init cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token"
]);
// Disable SSL check strictly for debugging if needed, otherwise keep true
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$data = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200 && $data) {
    header("Content-Type: $contentType");
    echo $data;
} else {
    // Fallback image (1x1 pixel transparent or placeholder)
    header("Location: /assets/img/ibc_logo_original.webp");
}
