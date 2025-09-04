<?php
/**
 * Test script to verify food sections endpoints work
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Config\Database;

echo "=== Testing Food Sections Endpoints ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Get a store to test with
    $query = "SELECT id, user_id, store_name FROM stores LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "❌ No stores found in database\n";
        exit;
    }
    
    echo "Using store: ID=" . $store['id'] . ", Name=" . $store['store_name'] . "\n";
    
    // Test the model directly
    echo "\n1. Testing FoodItem model directly...\n";
    $foodItemModel = new FoodItem();
    
    // Test getAllFoodSectionsByStoreId
    echo "Testing getAllFoodSectionsByStoreId...\n";
    $sections = $foodItemModel->getAllFoodSectionsByStoreId($store['id'], 10, 0);
    echo "Result: " . count($sections) . " sections found\n";
    
    if (!empty($sections)) {
        echo "First section: " . json_encode($sections[0]) . "\n";
    }
    
    // Test countFoodSectionsByStoreId
    echo "\nTesting countFoodSectionsByStoreId...\n";
    $count = $foodItemModel->countFoodSectionsByStoreId($store['id']);
    echo "Count result: " . $count . "\n";
    
    // Test getFoodSectionById if we have sections
    if (!empty($sections)) {
        echo "\nTesting getFoodSectionById...\n";
        $sectionId = $sections[0]['id'];
        $section = $foodItemModel->getFoodSectionById($sectionId);
        
        if ($section) {
            echo "Section found: " . json_encode($section) . "\n";
        } else {
            echo "Section not found\n";
        }
    }
    
    // Test the controller
    echo "\n2. Testing FoodItemController...\n";
    $controller = new \Controller\FoodItemController();
    
    // Mock user data
    $user = [
        'user_id' => $store['user_id'],
        'role' => 'merchant',
        'store_id' => $store['id']
    ];
    
    echo "Testing getAllFoodSectionsByStoreId controller method...\n";
    $response = $controller->getAllFoodSectionsByStoreId($store['id'], $user, 1, 10);
    echo "Controller response: " . json_encode($response) . "\n";
    
    if (!empty($sections)) {
        echo "\nTesting getFoodSectionById controller method...\n";
        $sectionId = $sections[0]['id'];
        $response = $controller->getFoodSectionById($sectionId, $user, $store['id']);
        echo "Controller response: " . json_encode($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
