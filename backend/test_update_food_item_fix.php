<?php
/**
 * Test script to verify the food item update fix
 * Tests updating photo, sides, packs, and sections
 */

require_once 'config/Database.php';
require_once 'controller/FoodItemController.php';
require_once 'model/FoodItem.php';

use Controller\FoodItemController;
use Model\FoodItem;

echo "=== FOOD ITEM UPDATE FIX TEST ===\n\n";

// Test data for updating food item
$testData = [
    'id' => 1, // Replace with actual food item ID
    'name' => 'Updated Test Item',
    'short_description' => 'Updated description',
    'price' => 15.99,
    'category_id' => 1, // Replace with actual category ID
    'sides' => [
        'required' => true,
        'max_quantity' => 2,
        'items' => [1, 2] // Replace with actual side IDs
    ],
    'packs' => [
        'required' => false,
        'max_quantity' => 1,
        'items' => [1] // Replace with actual pack IDs
    ],
    'sections' => [
        [
            'section_id' => 1, // Replace with actual section ID
            'required' => true,
            'max_quantity' => 1,
            'item_ids' => [1, 2] // Replace with actual item IDs
        ]
    ]
];

// Mock user data (replace with actual user data)
$user = [
    'user_id' => 1,
    'store_id' => 1,
    'role' => 'merchant'
];

try {
    $controller = new FoodItemController();
    
    echo "Testing update with all fields...\n";
    echo "Data: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 1: Update with all fields including complex ones
    $result = $controller->update($testData, $user);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Update with only basic fields (name, description, price)
    $basicData = [
        'id' => 1,
        'name' => 'Updated Basic Item',
        'short_description' => 'Updated basic description',
        'price' => 12.99
    ];
    
    echo "Testing update with only basic fields...\n";
    echo "Data: " . json_encode($basicData, JSON_PRETTY_PRINT) . "\n\n";
    
    $result2 = $controller->update($basicData, $user);
    echo "Result: " . json_encode($result2, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Update with only sides
    $sidesData = [
        'id' => 1,
        'sides' => [
            'required' => false,
            'max_quantity' => 3,
            'items' => [1, 2, 3]
        ]
    ];
    
    echo "Testing update with only sides...\n";
    echo "Data: " . json_encode($sidesData, JSON_PRETTY_PRINT) . "\n\n";
    
    $result3 = $controller->update($sidesData, $user);
    echo "Result: " . json_encode($result3, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 4: Update with only packs
    $packsData = [
        'id' => 1,
        'packs' => [
            'required' => true,
            'max_quantity' => 2,
            'items' => [1, 2]
        ]
    ];
    
    echo "Testing update with only packs...\n";
    echo "Data: " . json_encode($packsData, JSON_PRETTY_PRINT) . "\n\n";
    
    $result4 = $controller->update($packsData, $user);
    echo "Result: " . json_encode($result4, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 5: Update with only sections
    $sectionsData = [
        'id' => 1,
        'sections' => [
            [
                'section_id' => 1,
                'required' => false,
                'max_quantity' => 2,
                'item_ids' => [1, 2, 3]
            ]
        ]
    ];
    
    echo "Testing update with only sections...\n";
    echo "Data: " . json_encode($sectionsData, JSON_PRETTY_PRINT) . "\n\n";
    
    $result5 = $controller->update($sectionsData, $user);
    echo "Result: " . json_encode($result5, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "=== ALL TESTS COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
