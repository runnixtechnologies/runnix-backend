<?php
/**
 * Test script for multipart/form-data PUT requests
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== MULTIPART PUT TEST ===\n\n";

// Test data as form fields
$testData = [
    'id' => '1',
    'name' => 'Test Burger Multipart',
    'short_description' => 'Testing multipart PUT',
    'price' => '12.99',
    'category_id' => '1'
];

echo "Test Data:\n";
foreach ($testData as $key => $value) {
    echo "  $key: $value\n";
}
echo "\n";

// Create multipart form data
$boundary = '----WebKitFormBoundary' . uniqid();
$body = '';

foreach ($testData as $key => $value) {
    $body .= "--$boundary\r\n";
    $body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
    $body .= "$value\r\n";
}
$body .= "--$boundary--\r\n";

echo "Multipart Body:\n";
echo substr($body, 0, 200) . "...\n\n";

// Test the PUT endpoint
$url = 'https://api.runnix.africa/api/update_food_item.php';
$headers = [
    'Content-Type: multipart/form-data; boundary=' . $boundary,
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
];

echo "Making PUT request to: " . $url . "\n";
echo "Content-Type: multipart/form-data; boundary=" . $boundary . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if ($error) {
    echo "cURL Error: " . $error . "\n";
}

echo "\n=== CHECK ERROR LOGS ===\n";
echo "After running this test, check the error logs for:\n";
echo "1. 'PUT request with multipart/form-data'\n";
echo "2. 'Detected boundary: [boundary]'\n";
echo "3. 'Number of parts: [count]'\n";
echo "4. 'Found field: [field] = [value]'\n";
echo "5. 'Parsed form-data: [data]'\n\n";

echo "=== EXPECTED LOG OUTPUT ===\n";
echo "PUT request with multipart/form-data\n";
echo "Detected boundary: --$boundary\n";
echo "Number of parts: [should be > 1]\n";
echo "Found field: id = 1\n";
echo "Found field: name = Test Burger Multipart\n";
echo "Found field: short_description = Testing multipart PUT\n";
echo "Found field: price = 12.99\n";
echo "Found field: category_id = 1\n";
echo "Parsed form-data: {\"id\":\"1\",\"name\":\"Test Burger Multipart\",...}\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Replace YOUR_JWT_TOKEN_HERE with a valid token\n";
echo "2. Run this test\n";
echo "3. Check the error logs for the multipart parsing\n";
echo "4. If parsing works, the issue might be elsewhere\n";
echo "5. If parsing fails, we need to fix the multipart handler\n";
?>
