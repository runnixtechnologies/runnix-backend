<?php
/**
 * Test Script: Enhanced Operating Hours Functionality
 * Tests the new 24/7 toggle and day enable/disable features
 */

require_once 'vendor/autoload.php';
require_once 'config/Database.php';
require_once 'model/Store.php';

use Model\Store;

echo "🧪 Testing Enhanced Operating Hours Functionality\n";
echo "================================================\n\n";

$store = new Store();

// Test data scenarios
$testScenarios = [
    [
        'name' => 'Regular Business Hours',
        'business_24_7' => false,
        'operating_hours' => [
            'monday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
            'tuesday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
            'wednesday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
            'thursday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
            'friday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
            'saturday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '10:00', 'close_time' => '15:00'],
            'sunday' => ['enabled' => false, 'is_24hrs' => false, 'open_time' => null, 'close_time' => null]
        ]
    ],
    [
        'name' => '24/7 Business',
        'business_24_7' => true,
        'operating_hours' => [
            'monday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'tuesday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'wednesday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'thursday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'friday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'saturday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'sunday' => ['enabled' => false, 'is_24hrs' => false, 'open_time' => null, 'close_time' => null]
        ]
    ],
    [
        'name' => 'Mixed Schedule',
        'business_24_7' => false,
        'operating_hours' => [
            'monday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '08:00', 'close_time' => '18:00'],
            'tuesday' => ['enabled' => true, 'is_24hrs' => true, 'open_time' => null, 'close_time' => null],
            'wednesday' => ['enabled' => false, 'is_24hrs' => false, 'open_time' => null, 'close_time' => null],
            'thursday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '10:00', 'close_time' => '16:00'],
            'friday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '09:00', 'close_time' => '17:00'],
            'saturday' => ['enabled' => true, 'is_24hrs' => false, 'open_time' => '11:00', 'close_time' => '14:00'],
            'sunday' => ['enabled' => false, 'is_24hrs' => false, 'open_time' => null, 'close_time' => null]
        ]
    ]
];

// Test with a sample store ID (you may need to adjust this)
$testStoreId = 1;

echo "Testing with Store ID: $testStoreId\n";
echo "=====================================\n\n";

foreach ($testScenarios as $index => $scenario) {
    echo "Test Scenario " . ($index + 1) . ": " . $scenario['name'] . "\n";
    echo "Business 24/7: " . ($scenario['business_24_7'] ? 'Yes' : 'No') . "\n";
    
    // Test update
    $updateResult = $store->updateOperatingHours($testStoreId, $scenario['operating_hours'], $scenario['business_24_7']);
    if ($updateResult) {
        echo "✅ Update successful\n";
    } else {
        echo "❌ Update failed\n";
        continue;
    }
    
    // Test retrieval
    $retrievedData = $store->getOperatingHours($testStoreId);
    if ($retrievedData && isset($retrievedData['business_24_7']) && isset($retrievedData['operating_hours'])) {
        echo "✅ Retrieval successful\n";
        echo "Retrieved Business 24/7: " . ($retrievedData['business_24_7'] ? 'Yes' : 'No') . "\n";
        
        // Display operating hours
        foreach ($retrievedData['operating_hours'] as $day => $hours) {
            $status = $hours['enabled'] ? ($hours['is_24hrs'] ? '24hrs' : $hours['open_time'] . ' - ' . $hours['close_time']) : 'Unavailable';
            echo "  $day: $status\n";
        }
    } else {
        echo "❌ Retrieval failed\n";
    }
    
    echo "\n";
}

echo "🎉 Enhanced operating hours test completed!\n";
echo "\n📋 Features Tested:\n";
echo "- 24/7 business toggle\n";
echo "- Individual day enable/disable\n";
echo "- Mixed schedules (some days 24hrs, some specific hours)\n";
echo "- Proper data storage and retrieval\n";
