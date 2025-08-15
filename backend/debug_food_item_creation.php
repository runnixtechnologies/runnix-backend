<?php
/**
 * Debug Script for Food Item Creation
 * This script helps identify why food item creation is failing
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
require_once 'config/cors.php';
require_once 'middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

echo "=== FOOD ITEM CREATION DEBUG ===\n\n";

// Simulate the request data
$testData = [
    'name' => 'Deluxe Boli',
    'price' => 52.99,
    'category_id' => 1,
    'user_id' => 21,
    'short_description' => 'Juicy beef burger with all the fixings',
    'sides' => ['required' => true, 'max_quantity' => 2, 'items' => [7, 8, 9]],
    'packs' => ['required' => false, 'max_quantity' => 1, 'items' => [1, 2]],
    'sections' => ['required' => true, 'max_quantity' => 3, 'items' => [1, 2]]
];

echo "Test Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT);
echo "\n\n";

// Test validation logic
echo "=== VALIDATION TESTS ===\n\n";

// Test 1: Name validation
echo "1. Testing name validation:\n";
if (!isset($testData['name']) || empty(trim($testData['name']))) {
    echo "❌ Name validation failed: name is missing or empty\n";
} else {
    echo "✅ Name validation passed: '{$testData['name']}'\n";
}
echo "\n";

// Test 2: Price validation
echo "2. Testing price validation:\n";
if (!isset($testData['price']) || !is_numeric($testData['price']) || $testData['price'] < 0) {
    echo "❌ Price validation failed: price is invalid\n";
} else {
    echo "✅ Price validation passed: {$testData['price']}\n";
}
echo "\n";

// Test 3: Category validation
echo "3. Testing category validation:\n";
if (!isset($testData['category_id']) || empty($testData['category_id'])) {
    echo "❌ Category validation failed: category_id is missing\n";
} else {
    echo "✅ Category validation passed: {$testData['category_id']}\n";
}
echo "\n";

// Test 4: Check database connection and category
echo "4. Testing database connection and category:\n";
try {
    require_once 'config/Database.php';
    use Config\Database;
    
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if category exists
    $categoryCheck = $conn->prepare("SELECT id, name, status FROM categories WHERE id = :category_id");
    $categoryCheck->execute(['category_id' => $testData['category_id']]);
    $category = $categoryCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        echo "✅ Category found: ID {$category['id']}, Name: {$category['name']}, Status: {$category['status']}\n";
    } else {
        echo "❌ Category not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Test authentication (simulate)
echo "5. Testing authentication simulation:\n";
$mockUser = [
    'user_id' => 21,
    'store_id' => 1,
    'role' => 'merchant'
];

if (!isset($mockUser['user_id'])) {
    echo "❌ User ID not found in authentication\n";
} else {
    echo "✅ User ID found: {$mockUser['user_id']}\n";
}

if (!isset($mockUser['store_id'])) {
    echo "❌ Store ID not found in authentication\n";
} else {
    echo "✅ Store ID found: {$mockUser['store_id']}\n";
}
echo "\n";

// Test 6: Check store exists
echo "6. Testing store existence:\n";
try {
    $storeCheck = $conn->prepare("SELECT id, store_name FROM stores WHERE id = :store_id");
    $storeCheck->execute(['store_id' => $mockUser['store_id']]);
    $store = $storeCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($store) {
        echo "✅ Store found: ID {$store['id']}, Name: {$store['store_name']}\n";
    } else {
        echo "❌ Store not found\n";
    }
} catch (Exception $e) {
    echo "❌ Store check error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Test the actual controller method
echo "7. Testing controller method:\n";
try {
    $controller = new FoodItemController();
    
    // Add user and store data to test data
    $testData['user_id'] = $mockUser['user_id'];
    $testData['store_id'] = $mockUser['store_id'];
    
    echo "Calling controller->create() with data:\n";
    echo json_encode($testData, JSON_PRETTY_PRINT);
    echo "\n\n";
    
    $result = $controller->create($testData, $mockUser);
    
    echo "Controller result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
