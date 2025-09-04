<?php
// Test script for get_packby_id endpoint

// Test data
$baseUrl = 'http://localhost/runnix/backend/api/get_packby_id.php';
$packId = 1; // Replace with actual pack ID
$token = 'your_jwt_token_here'; // Replace with actual JWT token

// Test GET request with token in URL
$url = $baseUrl . '?id=' . $packId . '&token=' . $token;

echo "Testing GET request to: $url\n\n";

// Make GET request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test GET request with Authorization header
echo "Testing GET request with Authorization header...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '?id=' . $packId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";
?>
