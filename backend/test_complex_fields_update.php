<?php
/**
 * Test script to verify complex fields (sides, packs, sections) update
 */

require_once 'config/Database.php';
require_once 'controller/FoodItemController.php';
require_once 'model/FoodItem.php';

use Controller\FoodItemController;
use Model\FoodItem;

echo "=== COMPLEX FIELDS UPDATE TEST ===\n\n";

// Test data for updating food item with complex fields
$testData = [
    'id' => 31, // Replace with actual food item ID
    'name' => 'Test Complex Fields Update',
    'short_description' => 'Testing sides, packs, and sections update',
    'price' => 25.99,
    'category_id' => 1,
    'sides' => '{"required": true,"max_quantity": 2,"items": [11]}', // JSON string
    'packs' => '{"required": false,"max_quantity": 1,"items": [13,14]}', // JSON string
    'sections' => '[{"section_id": 12, "required": true,"max_quantity": 1,"item_ids": [82, 83]}]' // JSON string
];

// Mock user data (replace with actual user data)
$user = [
    'user_id' => 21,
    'store_id' => 12,
    'role' => 'merchant'
];

try {
    $controller = new FoodItemController();
    
    echo "Testing update with complex fields...\n";
    echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test the update
    $result = $controller->update($testData, $user);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test with array format (already parsed)
    $testDataArray = [
        'id' => 31,
        'name' => 'Test Complex Fields Update Array',
        'short_description' => 'Testing with array format',
        'price' => 30.99,
        'category_id' => 1,
        'sides' => [
            'required' => true,
            'max_quantity' => 3,
            'items' => [11, 12]
        ],
        'packs' => [
            'required' => true,
            'max_quantity' => 2,
            'items' => [13]
        ],
        'sections' => [
            [
                'section_id' => 12,
                'required' => false,
                'max_quantity' => 2,
                'item_ids' => [82, 83, 93]
            ]
        ]
    ];
    
    echo "Testing update with array format...\n";
    echo "Data: " . json_encode($testDataArray, JSON_PRETTY_PRINT) . "\n\n";
    
    $result2 = $controller->update($testDataArray, $user);
    echo "Result: " . json_encode($result2, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "=== ALL TESTS COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
