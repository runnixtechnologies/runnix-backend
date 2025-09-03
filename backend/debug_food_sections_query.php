<?php
/**
 * Debug script to test the food sections query step by step
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Config\Database;

echo "=== Debugging Food Sections Query ===\n\n";

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
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Test the basic query without pagination
echo "Test 2: Testing basic query without pagination\n";
echo "-----------------------------------------------\n";

try {
    $storeId = 1; // Assuming store_id 1 exists
    $query = "SELECT fs.id, fs.store_id, fs.section_name as name, fs.max_quantity, fs.required, fs.price, fs.status, fs.created_at, fs.updated_at
              FROM food_sections fs
              WHERE fs.store_id = :store_id 
              ORDER BY fs.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Basic query result: " . count($sections) . " sections found\n";
    if (!empty($sections)) {
        echo "First section: " . json_encode($sections[0]) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Test the query with pagination
echo "Test 3: Testing query with pagination\n";
echo "------------------------------------\n";

try {
    $storeId = 1;
    $limit = 10;
    $offset = 0;
    
    $query = "SELECT fs.id, fs.store_id, fs.section_name as name, fs.max_quantity, fs.required, fs.price, fs.status, fs.created_at, fs.updated_at
              FROM food_sections fs
              WHERE fs.store_id = :store_id 
              ORDER BY fs.created_at DESC
              LIMIT :offset, :limit";
    
    echo "Query: " . $query . "\n";
    echo "Parameters: store_id=" . $storeId . ", limit=" . $limit . ", offset=" . $offset . "\n";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Paginated query result: " . count($sections) . " sections found\n";
    if (!empty($sections)) {
        echo "First section: " . json_encode($sections[0]) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 4: Test the model method directly
echo "Test 4: Testing model method directly\n";
echo "------------------------------------\n";

try {
    $storeId = 1;
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

// Test 5: Check if there are any sections for different store IDs
echo "Test 5: Checking sections for different store IDs\n";
echo "------------------------------------------------\n";

try {
    $query = "SELECT store_id, COUNT(*) as section_count FROM food_sections GROUP BY store_id";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $storeCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Sections per store:\n";
    foreach ($storeCounts as $storeCount) {
        echo "  - Store ID " . $storeCount['store_id'] . ": " . $storeCount['section_count'] . " sections\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
