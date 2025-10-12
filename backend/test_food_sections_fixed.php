<?php
/**
 * Test script to verify the fixed food sections endpoints
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Config\Database;

echo "=== Testing Fixed Food Sections Endpoints ===\n\n";

$foodItemModel = new FoodItem();
$db = new Database();
$conn = $db->getConnection();

// Test 1: Check if food_sections table exists and has data
echo "Test 1: Checking food_sections table\n";
echo "------------------------------------\n";

try {
    $query = "SELECT COUNT(*) as total FROM food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total sections in database: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        $query = "SELECT id, store_id, section_name, status FROM food_sections LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample sections:\n";
        foreach ($sections as $section) {
            echo "  - ID: " . $section['id'] . ", Store: " . $section['store_id'] . ", Name: " . $section['section_name'] . ", Status: " . $section['status'] . "\n";
        }
    } else {
        echo "No sections found. You may need to create some test data.\n";
        exit;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit;
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Test the fixed query with pagination
echo "Test 2: Testing fixed query with pagination\n";
echo "-------------------------------------------\n";

try {
    $storeId = $sections[0]['store_id']; // Use the first section's store_id
    $limit = 10;
    $offset = 0;
    
    echo "Testing with store_id: " . $storeId . "\n";
    
    $query = "SELECT fs.id, fs.store_id, fs.section_name as name, fs.max_quantity, fs.required, fs.price, fs.status, fs.created_at, fs.updated_at
              FROM food_sections fs
              WHERE fs.store_id = :store_id 
              ORDER BY fs.id DESC
              LIMIT :offset, :limit";
    
    echo "Query: " . $query . "\n";
    echo "Parameters: store_id=" . $storeId . ", limit=" . $limit . ", offset=" . $offset . "\n";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Query result: " . count($sections) . " sections found\n";
    
    if (!empty($sections)) {
        echo "First section: " . json_encode($sections[0]) . "\n";
    }
    
    // Check for any PDO errors
    $errorInfo = $stmt->errorInfo();
    if ($errorInfo[0] !== '00000') {
        echo "PDO Error: " . json_encode($errorInfo) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Test the model method directly
echo "Test 3: Testing model method directly\n";
echo "------------------------------------\n";

try {
    $storeId = $sections[0]['store_id'];
    $limit = 10;
    $offset = 0;
    
    echo "Calling getAllFoodSectionsByStoreId with storeId: " . $storeId . ", limit: " . $limit . ", offset: " . $offset . "\n";
    
    $sections = $foodItemModel->getAllFoodSectionsByStoreId($storeId, $limit, $offset);
    
    echo "Model method result: " . count($sections) . " sections returned\n";
    if (!empty($sections)) {
        echo "First section: " . json_encode($sections[0]) . "\n";
    } else {
        echo "No sections returned from model method\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 4: Test getFoodSectionById method
echo "Test 4: Testing getFoodSectionById method\n";
echo "----------------------------------------\n";

try {
    $sectionId = $sections[0]['id'];
    
    echo "Testing getFoodSectionById with section ID: " . $sectionId . "\n";
    
    $section = $foodItemModel->getFoodSectionById($sectionId);
    
    if ($section) {
        echo "✅ Section found:\n";
        echo "  - ID: " . $section['id'] . "\n";
        echo "  - Name: " . $section['name'] . "\n";
        echo "  - Store ID: " . $section['store_id'] . "\n";
        echo "  - Status: " . $section['status'] . "\n";
        echo "  - Max Quantity: " . $section['max_quantity'] . "\n";
        echo "  - Required: " . ($section['required'] ? 'Yes' : 'No') . "\n";
        echo "  - Price: " . $section['price'] . "\n";
    } else {
        echo "❌ Section not found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the actual API endpoints:\n";
echo "1. Ensure you have a valid merchant JWT token\n";
echo "2. Test get_all_foodsections.php: GET /api/get_all_foodsections.php\n";
echo "3. Test get_foodsectionby_id.php: GET /api/get_foodsectionby_id.php?id=<section_id>\n";
echo "4. Include the Authorization header: Bearer <your_jwt_token>\n";
?>
