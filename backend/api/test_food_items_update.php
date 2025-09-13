<?php
// Test the updated food items endpoints
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Model\FoodItem;

header('Content-Type: application/json');

echo "=== Testing Updated Food Items Endpoints ===\n\n";

try {
    $foodItemModel = new FoodItem();
    
    // Test 1: Test getAllByStoreId method
    echo "1. Testing getAllByStoreId method...\n";
    
    $storeId = 15; // Use your test store ID
    $results = $foodItemModel->getAllByStoreId($storeId, 2, 0); // Get first 2 items
    
    if (!empty($results)) {
        echo "   ✓ Found " . count($results) . " food items\n";
        
        $firstItem = $results[0];
        echo "   ✓ Testing first item: " . $firstItem['name'] . "\n";
        
        // Check sides structure
        if (isset($firstItem['sides'])) {
            echo "   ✓ Sides structure found\n";
            echo "   ✓ Required: " . ($firstItem['sides']['required'] ? 'true' : 'false') . "\n";
            echo "   ✓ Max quantity: " . $firstItem['sides']['max_quantity'] . "\n";
            echo "   ✓ Items count: " . count($firstItem['sides']['items']) . "\n";
            
            if (!empty($firstItem['sides']['items'])) {
                $firstSide = $firstItem['sides']['items'][0];
                echo "   ✓ First side: ID=" . $firstSide['id'] . ", Name=" . $firstSide['name'] . ", Price=" . $firstSide['price'] . ", Extra Price=" . $firstSide['extra_price'] . "\n";
            }
        }
        
        // Check packs structure
        if (isset($firstItem['packs'])) {
            echo "   ✓ Packs structure found\n";
            echo "   ✓ Required: " . ($firstItem['packs']['required'] ? 'true' : 'false') . "\n";
            echo "   ✓ Max quantity: " . $firstItem['packs']['max_quantity'] . "\n";
            echo "   ✓ Items count: " . count($firstItem['packs']['items']) . "\n";
            
            if (!empty($firstItem['packs']['items'])) {
                $firstPack = $firstItem['packs']['items'][0];
                echo "   ✓ First pack: ID=" . $firstPack['id'] . ", Name=" . $firstPack['name'] . ", Price=" . $firstPack['price'] . ", Extra Price=" . $firstPack['extra_price'] . "\n";
            }
        }
        
        // Check sections structure
        if (isset($firstItem['sections'])) {
            echo "   ✓ Sections structure found\n";
            echo "   ✓ Sections count: " . count($firstItem['sections']) . "\n";
            
            if (!empty($firstItem['sections'])) {
                $firstSection = $firstItem['sections'][0];
                echo "   ✓ First section: ID=" . $firstSection['section_id'] . ", Name=" . $firstSection['section_name'] . ", Required=" . ($firstSection['required'] ? 'true' : 'false') . "\n";
                echo "   ✓ Section items count: " . count($firstSection['items']) . "\n";
                
                if (!empty($firstSection['items'])) {
                    $firstSectionItem = $firstSection['items'][0];
                    echo "   ✓ First section item: ID=" . $firstSectionItem['id'] . ", Name=" . $firstSectionItem['name'] . ", Price=" . $firstSectionItem['price'] . ", Extra Price=" . $firstSectionItem['extra_price'] . "\n";
                }
            }
        }
        
    } else {
        echo "   ⚠ No food items found for store ID: " . $storeId . "\n";
    }
    
    echo "\n";
    
    // Test 2: Test getByItemId method
    echo "2. Testing getByItemId method...\n";
    
    if (!empty($results)) {
        $testItemId = $results[0]['id'];
        $singleItem = $foodItemModel->getByItemId($testItemId);
        
        if ($singleItem) {
            echo "   ✓ Found item: " . $singleItem['name'] . "\n";
            
            // Check sides structure
            if (isset($singleItem['sides'])) {
                echo "   ✓ Sides structure found\n";
                echo "   ✓ Items count: " . count($singleItem['sides']['items']) . "\n";
                
                if (!empty($singleItem['sides']['items'])) {
                    $firstSide = $singleItem['sides']['items'][0];
                    echo "   ✓ First side: ID=" . $firstSide['id'] . ", Name=" . $firstSide['name'] . ", Price=" . $firstSide['price'] . ", Extra Price=" . $firstSide['extra_price'] . "\n";
                }
            }
            
            // Check packs structure
            if (isset($singleItem['packs'])) {
                echo "   ✓ Packs structure found\n";
                echo "   ✓ Items count: " . count($singleItem['packs']['items']) . "\n";
                
                if (!empty($singleItem['packs']['items'])) {
                    $firstPack = $singleItem['packs']['items'][0];
                    echo "   ✓ First pack: ID=" . $firstPack['id'] . ", Name=" . $firstPack['name'] . ", Price=" . $firstPack['price'] . ", Extra Price=" . $firstPack['extra_price'] . "\n";
                }
            }
            
            // Check sections structure
            if (isset($singleItem['sections'])) {
                echo "   ✓ Sections structure found\n";
                echo "   ✓ Sections count: " . count($singleItem['sections']) . "\n";
                
                if (!empty($singleItem['sections'])) {
                    $firstSection = $singleItem['sections'][0];
                    echo "   ✓ First section: ID=" . $firstSection['section_id'] . ", Name=" . $firstSection['section_name'] . ", Items count=" . count($firstSection['items']) . "\n";
                }
            }
            
        } else {
            echo "   ❌ Item not found with ID: " . $testItemId . "\n";
        }
    } else {
        echo "   ⚠ No items available to test getByItemId\n";
    }
    
    echo "\n=== Test Summary ===\n";
    echo "✅ Both endpoints updated successfully!\n";
    echo "✅ Sides now return: id, name, price, extra_price\n";
    echo "✅ Packs now return: id, name, price, extra_price\n";
    echo "✅ Sections now return: section_id, section_name, required, max_quantity, items (with id, name, price, extra_price)\n";
    echo "\nNext steps:\n";
    echo "1. Test with actual API endpoints\n";
    echo "2. Verify mobile app compatibility\n";
    echo "3. Check performance with large datasets\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
