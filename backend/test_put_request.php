<?php
/**
 * Test script to debug PUT request handling
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== PUT REQUEST DEBUG TEST ===\n\n";

// Test data
$testData = [
    'id' => 1, // Replace with actual food item ID
    'name' => 'Test Burger PUT',
    'short_description' => 'Testing PUT request',
    'price' => 12.99,
    'category_id' => 1
];

echo "Test Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Test the PUT endpoint
$url = 'http://localhost/runnix/backend/api/update_food_item.php';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
];

echo "Making PUT request to: " . $url . "\n";
echo "Headers: " . json_encode($headers) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // Use PUT method
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
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
echo "1. '=== PROCESSING PUT REQUEST ==='\n";
echo "2. 'Raw PUT input:'\n";
echo "3. 'Parsed JSON Data:'\n";
echo "4. 'Final processed data:'\n";
echo "5. Any authentication errors\n";
echo "6. Any controller errors\n\n";

echo "=== COMMON PUT ISSUES ===\n";
echo "1. Content-Type header missing or incorrect\n";
echo "2. JSON data not properly formatted\n";
echo "3. Authentication token missing or invalid\n";
echo "4. Server not configured to handle PUT requests\n";
echo "5. Data parsing issues\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Replace YOUR_JWT_TOKEN_HERE with a valid token\n";
echo "2. Replace food item ID with an actual ID\n";
echo "3. Run this test\n";
echo "4. Check the error logs for detailed information\n";
echo "5. Share the error log details\n";
?>
