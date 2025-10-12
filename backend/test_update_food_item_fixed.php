<?php
/**
 * Test script for the updated food item endpoint
 * This tests the fixes for photo, sides, packs, and sections updates
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once 'config/cors.php';

// Test data simulating what the mobile app would send
$testData = [
    'id' => 1, // Replace with actual food item ID
    'name' => 'Updated Burger',
    'short_description' => 'Updated description for the burger',
    'price' => 15.99,
    'sides' => [
        'required' => true,
        'max_quantity' => 2,
        'items' => [1, 2, 3] // Side IDs
    ],
    'packs' => [
        'required' => false,
        'max_quantity' => 1,
        'items' => [1, 2] // Pack IDs
    ],
    'sections' => [
        [
            'section_id' => 1,
            'required' => true,
            'max_quantity' => 1,
            'item_ids' => [1, 2, 3]
        ],
        [
            'section_id' => 2,
            'required' => false,
            'max_quantity' => 2,
            'item_ids' => [4, 5]
        ]
    ]
];

echo "=== TESTING UPDATED FOOD ITEM ENDPOINT ===\n";
echo "Test Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Test the endpoint
$url = 'http://localhost/runnix/backend/api/update_food_item.php';
$headers = [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
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

echo "\n=== TEST COMPLETE ===\n";
echo "Expected: HTTP 200 with success message\n";
echo "The update should now handle:\n";
echo "- Photo updates (if provided)\n";
echo "- Sides updates with required/max_quantity configuration\n";
echo "- Packs updates with required/max_quantity configuration\n";
echo "- Sections updates with multiple section configurations\n";
?>
