<?php
/**
 * Debug Script for Category Validation Issue
 * This script helps identify why category validation is failing
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== CATEGORY VALIDATION DEBUG ===\n\n";
    
    // Test parameters (you can change these)
    $testCategoryId = 1;
    $testStoreId = 1;
    
    echo "Testing with:\n";
    echo "- Category ID: {$testCategoryId}\n";
    echo "- Store ID: {$testStoreId}\n\n";
    
    // Check if category exists and is active
    echo "1. Checking if category exists and is active:\n";
    $categoryCheck = $conn->prepare("SELECT id, name, status, store_type_id FROM categories WHERE id = :category_id");
    $categoryCheck->execute(['category_id' => $testCategoryId]);
    $category = $categoryCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        echo "✅ Category found:\n";
        echo "   - ID: {$category['id']}\n";
        echo "   - Name: {$category['name']}\n";
        echo "   - Status: {$category['status']}\n";
        echo "   - Store Type ID: {$category['store_type_id']}\n";
    } else {
        echo "❌ Category not found\n";
    }
    echo "\n";
    
    // Check if store exists
    echo "2. Checking if store exists:\n";
    $storeCheck = $conn->prepare("SELECT id, store_name, store_type_id, user_id FROM stores WHERE id = :store_id");
    $storeCheck->execute(['store_id' => $testStoreId]);
    $store = $storeCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($store) {
        echo "✅ Store found:\n";
        echo "   - ID: {$store['id']}\n";
        echo "   - Name: {$store['store_name']}\n";
        echo "   - Store Type ID: {$store['store_type_id']}\n";
        echo "   - User ID: {$store['user_id']}\n";
    } else {
        echo "❌ Store not found\n";
    }
    echo "\n";
    
    // Check if store type exists
    echo "3. Checking if store type exists:\n";
    $storeTypeCheck = $conn->prepare("SELECT id, name, status FROM store_types WHERE id = :store_type_id");
    $storeTypeCheck->execute(['store_type_id' => $store['store_type_id'] ?? 0]);
    $storeType = $storeTypeCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($storeType) {
        echo "✅ Store type found:\n";
        echo "   - ID: {$storeType['id']}\n";
        echo "   - Name: {$storeType['name']}\n";
        echo "   - Status: {$storeType['status']}\n";
    } else {
        echo "❌ Store type not found\n";
    }
    echo "\n";
    
    // Test the original validation query
    echo "4. Testing the original validation query:\n";
    $originalQuery = "
        SELECT c.id FROM categories c 
        JOIN store_types st ON c.store_type_id = st.id 
        JOIN stores s ON s.store_type_id = st.id 
        WHERE c.id = :category_id AND s.id = :store_id AND c.status = 'active'
    ";
    
    echo "Query: " . str_replace([':category_id', ':store_id'], [$testCategoryId, $testStoreId], $originalQuery) . "\n";
    
    $validationCheck = $conn->prepare($originalQuery);
    $validationCheck->execute([
        'category_id' => $testCategoryId,
        'store_id' => $testStoreId
    ]);
    
    $result = $validationCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Original query returned result: {$result['id']}\n";
    } else {
        echo "❌ Original query returned no results\n";
    }
    echo "\n";
    
    // Test a simpler validation query
    echo "5. Testing simpler validation query:\n";
    $simpleQuery = "
        SELECT c.id FROM categories c 
        WHERE c.id = :category_id AND c.status = 'active'
    ";
    
    echo "Query: " . str_replace(':category_id', $testCategoryId, $simpleQuery) . "\n";
    
    $simpleCheck = $conn->prepare($simpleQuery);
    $simpleCheck->execute(['category_id' => $testCategoryId]);
    
    $simpleResult = $simpleCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($simpleResult) {
        echo "✅ Simple query returned result: {$simpleResult['id']}\n";
    } else {
        echo "❌ Simple query returned no results\n";
    }
    echo "\n";
    
    // Show all categories for the store's type
    echo "6. All categories for store type {$store['store_type_id']}:\n";
    $categoriesQuery = $conn->prepare("
        SELECT id, name, status, store_type_id 
        FROM categories 
        WHERE store_type_id = :store_type_id
    ");
    $categoriesQuery->execute(['store_type_id' => $store['store_type_id'] ?? 0]);
    $categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);
    
    if ($categories) {
        foreach ($categories as $cat) {
            $status = $cat['status'] == 'active' ? '✅' : '❌';
            echo "   {$status} ID: {$cat['id']}, Name: {$cat['name']}, Status: {$cat['status']}\n";
        }
    } else {
        echo "   No categories found for this store type\n";
    }
    echo "\n";
    
    // Show all stores
    echo "7. All stores:\n";
    $storesQuery = $conn->query("SELECT id, store_name, store_type_id FROM stores LIMIT 5");
    $stores = $storesQuery->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stores as $s) {
        echo "   - ID: {$s['id']}, Name: {$s['store_name']}, Type: {$s['store_type_id']}\n";
    }
    echo "\n";
    
    // Show all categories
    echo "8. All categories:\n";
    $allCategoriesQuery = $conn->query("SELECT id, name, status, store_type_id FROM categories LIMIT 5");
    $allCategories = $allCategoriesQuery->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allCategories as $cat) {
        $status = $cat['status'] == 'active' ? '✅' : '❌';
        echo "   {$status} ID: {$cat['id']}, Name: {$cat['name']}, Type: {$cat['store_type_id']}, Status: {$cat['status']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
