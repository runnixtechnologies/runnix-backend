<?php
/**
 * Test script to verify operating hours endpoints work correctly
 * This tests both GET and PUT endpoints with proper error handling
 */

require_once '../vendor/autoload.php';

use Controller\StoreController;
use Model\Store;

echo "=== Operating Hours Endpoint Test ===\n\n";

// Test 1: Check if Store model can handle missing columns gracefully
echo "1. Testing Store model backward compatibility...\n";
$store = new Store();

// Simulate a test store ID (you'll need to replace with a real one)
$testStoreId = 1; // Replace with actual store ID from your database

try {
    $operatingHours = $store->getOperatingHours($testStoreId);
    if ($operatingHours !== false) {
        echo "✅ Store model works with existing database structure\n";
        echo "   Business 24/7: " . ($operatingHours['business_24_7'] ? 'true' : 'false') . "\n";
        echo "   Operating hours count: " . count($operatingHours['operating_hours']) . "\n";
    } else {
        echo "❌ Store model failed to retrieve operating hours\n";
    }
} catch (Exception $e) {
    echo "❌ Store model error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if StoreController can extract store ID properly
echo "2. Testing StoreController store ID extraction...\n";
$controller = new StoreController();

// Simulate user object (you'll need to replace with real user data)
$testUser = [
    'user_id' => 1, // Replace with actual user ID
    'role' => 'merchant',
    'store_id' => null // This should trigger the fallback logic
];

try {
    // Test getOperatingHours
    $response = $controller->getOperatingHours($testUser);
    if (isset($response['status'])) {
        if ($response['status'] === 'success') {
            echo "✅ StoreController successfully extracted store ID and retrieved operating hours\n";
        } else {
            echo "❌ StoreController error: " . $response['message'] . "\n";
        }
    } else {
        echo "❌ StoreController returned unexpected response format\n";
    }
} catch (Exception $e) {
    echo "❌ StoreController error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Test update operating hours with sample data
echo "3. Testing update operating hours...\n";

$testData = [
    'business_24_7' => false,
    'operating_hours' => [
        'monday' => [
            'enabled' => true,
            'is_24hrs' => false,
            'is_closed' => false,
            'open_time' => '09:00',
            'close_time' => '17:00'
        ],
        'tuesday' => [
            'enabled' => true,
            'is_24hrs' => false,
            'is_closed' => false,
            'open_time' => '09:00',
            'close_time' => '17:00'
        ],
        'wednesday' => [
            'enabled' => false,
            'is_24hrs' => false,
            'is_closed' => true,
            'open_time' => null,
            'close_time' => null
        ]
    ]
];

try {
    $updateResponse = $controller->updateOperatingHours($testData, $testUser);
    if (isset($updateResponse['status'])) {
        if ($updateResponse['status'] === 'success') {
            echo "✅ StoreController successfully updated operating hours\n";
        } else {
            echo "❌ StoreController update error: " . $updateResponse['message'] . "\n";
        }
    } else {
        echo "❌ StoreController update returned unexpected response format\n";
    }
} catch (Exception $e) {
    echo "❌ StoreController update error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the actual API endpoints:\n";
echo "1. GET: curl -X GET 'your-domain/backend/api/get-operating-hours.php' -H 'Authorization: Bearer YOUR_JWT_TOKEN'\n";
echo "2. PUT: curl -X PUT 'your-domain/backend/api/update-operating-hours.php' -H 'Authorization: Bearer YOUR_JWT_TOKEN' -H 'Content-Type: application/json' -d 'JSON_DATA'\n";
echo "\nNote: Replace YOUR_JWT_TOKEN with actual merchant JWT token and JSON_DATA with the test data above.\n";
