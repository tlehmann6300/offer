<?php

$token = '0277d541c6bb7044e901a8a985ea74a9894df724';

$url = 'https://easyverein.com/api/v2.0/inventory-object?limit=100';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
        'User-Agent: easyVerein-Client'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die('cURL Fehler: ' . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "HTTP Fehler $httpCode\n";
    echo $response;
    exit;
}

$data = json_decode($response, true);

echo '<pre>';
print_r($data);
echo '</pre>';
