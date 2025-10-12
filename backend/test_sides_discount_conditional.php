<?php
/**
 * Test script to verify that sides endpoints only return discount data when discounts exist
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;

echo "=== Testing Sides Discount Conditional Logic ===\n\n";

$foodItemModel = new FoodItem();

// Test 1: Get all sides for a store (should not return discount fields if no discounts)
echo "Test 1: Testing getAllFoodSidesByStoreId\n";
echo "----------------------------------------\n";

try {
    $sides = $foodItemModel->getAllFoodSidesByStoreId(1, 5, 0); // Assuming store_id 1
    
    if (!empty($sides)) {
        echo "Found " . count($sides) . " sides\n";
        
        foreach ($sides as $index => $side) {
            echo "\nSide " . ($index + 1) . ":\n";
            echo "  ID: " . $side['id'] . "\n";
            echo "  Name: " . $side['name'] . "\n";
            echo "  Price: " . $side['price'] . "\n";
            
            // Check if discount fields exist
            if (isset($side['discount_id'])) {
                echo "  ✓ Has discount:\n";
                echo "    - Discount ID: " . $side['discount_id'] . "\n";
                echo "    - Percentage: " . $side['percentage'] . "\n";
                echo "    - Discount Price: " . $side['discount_price'] . "\n";
                echo "    - Start Date: " . $side['discount_start_date'] . "\n";
                echo "    - End Date: " . $side['discount_end_date'] . "\n";
            } else {
                echo "  ✗ No discount fields present (correct behavior)\n";
                echo "    - discount_id: " . (isset($side['discount_id']) ? 'present' : 'not present') . "\n";
                echo "    - percentage: " . (isset($side['percentage']) ? 'present' : 'not present') . "\n";
                echo "    - discount_price: " . (isset($side['discount_price']) ? 'present' : 'not present') . "\n";
                echo "    - discount_start_date: " . (isset($side['discount_start_date']) ? 'present' : 'not present') . "\n";
                echo "    - discount_end_date: " . (isset($side['discount_end_date']) ? 'present' : 'not present') . "\n";
            }
        }
    } else {
        echo "No sides found for store_id 1\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Get a specific side by ID
echo "Test 2: Testing getFoodSideById\n";
echo "--------------------------------\n";

if (!empty($sides)) {
    $firstSideId = $sides[0]['id'];
    
    try {
        $side = $foodItemModel->getFoodSideById($firstSideId);
        
        if ($side) {
            echo "Side ID " . $firstSideId . ":\n";
            echo "  Name: " . $side['name'] . "\n";
            echo "  Price: " . $side['price'] . "\n";
            
            // Check if discount fields exist
            if (isset($side['discount_id'])) {
                echo "  ✓ Has discount:\n";
                echo "    - Discount ID: " . $side['discount_id'] . "\n";
                echo "    - Percentage: " . $side['percentage'] . "\n";
                echo "    - Discount Price: " . $side['discount_price'] . "\n";
                echo "    - Start Date: " . $side['discount_start_date'] . "\n";
                echo "    - End Date: " . $side['discount_end_date'] . "\n";
            } else {
                echo "  ✗ No discount fields present (correct behavior)\n";
                echo "    - discount_id: " . (isset($side['discount_id']) ? 'present' : 'not present') . "\n";
                echo "    - percentage: " . (isset($side['percentage']) ? 'present' : 'not present') . "\n";
                echo "    - discount_price: " . (isset($side['discount_price']) ? 'present' : 'not present') . "\n";
                echo "    - discount_start_date: " . (isset($side['discount_start_date']) ? 'present' : 'not present') . "\n";
                echo "    - discount_end_date: " . (isset($side['discount_end_date']) ? 'present' : 'not present') . "\n";
            }
        } else {
            echo "Side not found\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No sides available to test getFoodSideById\n";
}

echo "\n=== Test Complete ===\n";
echo "\nExpected Behavior:\n";
echo "- Sides with discounts: Should return all discount fields\n";
echo "- Sides without discounts: Should NOT return any discount fields\n";
echo "- This ensures clean API responses without unnecessary null/empty discount data\n";
?>
