<?php
require_once '../vendor/autoload.php';
require_once 'config/Database.php';

use Config\Database;

$conn = (new Database())->getConnection();

echo "=== DEBUGGING DISCOUNT ISSUE ===\n";
echo "Store ID 12 (Your store) vs Store ID 15 (Mobile dev's store)\n\n";

// Check discount data for both stores
$stores = [12, 15];

foreach ($stores as $storeId) {
    echo "=== STORE ID: $storeId ===\n";
    
    // 1. Check if store exists
    $storeCheck = $conn->prepare("SELECT id, name FROM stores WHERE id = ?");
    $storeCheck->execute([$storeId]);
    $store = $storeCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "Store not found!\n\n";
        continue;
    }
    
    echo "Store: " . $store['name'] . "\n";
    
    // 2. Check discounts for this store
    $discountSql = "SELECT id, percentage, start_date, end_date, status, created_at 
                    FROM discounts 
                    WHERE store_id = ? AND status = 'active'";
    $discountStmt = $conn->prepare($discountSql);
    $discountStmt->execute([$storeId]);
    $discounts = $discountStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Active discounts: " . count($discounts) . "\n";
    foreach ($discounts as $discount) {
        echo "  - Discount ID: {$discount['id']}, Percentage: {$discount['percentage']}%, Status: {$discount['status']}\n";
        echo "    Start: {$discount['start_date']}, End: {$discount['end_date']}\n";
    }
    
    // 3. Check discount_items for this store
    $discountItemsSql = "SELECT di.*, d.percentage, d.start_date, d.end_date, d.status as discount_status
                         FROM discount_items di 
                         LEFT JOIN discounts d ON di.discount_id = d.id 
                         WHERE d.store_id = ? AND di.item_type = 'food_item'";
    $discountItemsStmt = $conn->prepare($discountItemsSql);
    $discountItemsStmt->execute([$storeId]);
    $discountItems = $discountItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Discount items (food_item type): " . count($discountItems) . "\n";
    foreach ($discountItems as $item) {
        echo "  - Item ID: {$item['item_id']}, Discount ID: {$item['discount_id']}, Percentage: {$item['percentage']}%\n";
        echo "    Start: {$item['start_date']}, End: {$item['end_date']}, Status: {$item['discount_status']}\n";
    }
    
    // 4. Check food items for this store
    $foodItemsSql = "SELECT id, name, price, status FROM food_items WHERE store_id = ? AND deleted = 0";
    $foodItemsStmt = $conn->prepare($foodItemsSql);
    $foodItemsStmt->execute([$storeId]);
    $foodItems = $foodItemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Food items: " . count($foodItems) . "\n";
    foreach ($foodItems as $item) {
        echo "  - ID: {$item['id']}, Name: {$item['name']}, Price: {$item['price']}, Status: {$item['status']}\n";
    }
    
    // 5. Test the exact query used in getAllByStoreId
    echo "\n--- Testing getAllByStoreId query ---\n";
    $testSql = "SELECT fi.id, fi.store_id, fi.category_id, fi.section_id, fi.user_id, fi.name, fi.price, fi.photo, 
                       fi.short_description, fi.max_qty, fi.status, fi.deleted, fi.created_at, fi.updated_at,
                       d.id as discount_id,
                       d.percentage as discount_percentage,
                       d.start_date as discount_start_date,
                       d.end_date as discount_end_date,
                       ROUND((fi.price - (fi.price * COALESCE(d.percentage, 0) / 100)), 2) as calculated_discount_price,
                       COALESCE(COUNT(DISTINCT oi.order_id), 0) as total_orders
                FROM food_items fi
                LEFT JOIN discount_items di ON fi.id = di.item_id AND di.item_type = 'food_item'
                LEFT JOIN discounts d ON di.discount_id = d.id AND d.store_id = ? AND d.status = 'active'
                LEFT JOIN order_items oi ON fi.id = oi.item_id
                WHERE fi.store_id = ? AND fi.deleted = 0 
                GROUP BY fi.id
                ORDER BY fi.created_at DESC";
    
    $testStmt = $conn->prepare($testSql);
    $testStmt->execute([$storeId, $storeId]);
    $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Query results: " . count($testResults) . " items\n";
    foreach ($testResults as $result) {
        echo "  - ID: {$result['id']}, Name: {$result['name']}, Price: {$result['price']}\n";
        echo "    Discount ID: " . ($result['discount_id'] ?? 'NULL') . "\n";
        echo "    Discount Percentage: " . ($result['discount_percentage'] ?? 'NULL') . "\n";
        echo "    Discount Start: " . ($result['discount_start_date'] ?? 'NULL') . "\n";
        echo "    Discount End: " . ($result['discount_end_date'] ?? 'NULL') . "\n";
        echo "    Calculated Discount Price: " . ($result['calculated_discount_price'] ?? 'NULL') . "\n";
        echo "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "=== ANALYSIS COMPLETE ===\n";
