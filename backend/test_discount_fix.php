<?php
require_once '../vendor/autoload.php';
require_once 'config/Database.php';

use Config\Database;
use Model\FoodItem;

$conn = (new Database())->getConnection();
$foodItem = new FoodItem();

echo "=== TESTING DISCOUNT FIX ===\n";
echo "Testing both store IDs to verify discount data is returned correctly\n\n";

$stores = [12, 15];

foreach ($stores as $storeId) {
    echo "=== STORE ID: $storeId ===\n";
    
    // Test the getAllByStoreId method
    $results = $foodItem->getAllByStoreId($storeId, 10, 0, false);
    
    echo "Number of food items: " . count($results) . "\n";
    
    foreach ($results as $item) {
        echo "  - ID: {$item['id']}, Name: {$item['name']}, Price: {$item['price']}\n";
        
        if (isset($item['discount_id']) && $item['discount_id']) {
            echo "    ✓ DISCOUNT FOUND:\n";
            echo "      Discount ID: {$item['discount_id']}\n";
            echo "      Percentage: {$item['percentage']}%\n";
            echo "      Start Date: {$item['discount_start_date']}\n";
            echo "      End Date: {$item['discount_end_date']}\n";
            echo "      Discount Price: {$item['discount_price']}\n";
        } else {
            echo "    ✗ No discount attached\n";
        }
        echo "\n";
    }
    
    echo str_repeat("-", 50) . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
echo "If you see discount details for store ID 15, the fix is working!\n";
