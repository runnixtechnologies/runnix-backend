<?php
/**
 * Debug script to test the sections endpoint
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Config\Database;

echo "=== Debugging Sections Endpoint ===\n\n";

$foodItemModel = new FoodItem();
$db = new Database();
$conn = $db->getConnection();

// Test 1: Check if there are any sections in the database
echo "Test 1: Checking database for sections\n";
echo "----------------------------------------\n";

try {
    // Simple query to see all sections
    $query = "SELECT * FROM food_sections LIMIT 5";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $allSections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total sections found in database: " . count($allSections) . "\n";
    
    if (!empty($allSections)) {
        echo "Sample sections:\n";
        foreach ($allSections as $section) {
            echo "  - ID: " . $section['id'] . ", Store ID: " . $section['store_id'] . ", Name: " . $section['name'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error querying database: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Test the getAllFoodSectionsByStoreId method
echo "Test 2: Testing getAllFoodSectionsByStoreId method\n";
echo "------------------------------------------------\n";

try {
    // Test with store_id 1 (assuming it exists)
    $sections = $foodItemModel->getAllFoodSectionsByStoreId(1, 5, 0);
    
    echo "Sections returned for store_id 1: " . count($sections) . "\n";
    
    if (!empty($sections)) {
        echo "Sections data:\n";
        foreach ($sections as $section) {
            echo "  - ID: " . $section['id'] . ", Name: " . $section['name'] . ", Store ID: " . $section['store_id'] . "\n";
        }
    } else {
        echo "No sections returned for store_id 1\n";
    }
} catch (Exception $e) {
    echo "Error calling getAllFoodSectionsByStoreId: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Check the count method
echo "Test 3: Testing countFoodSectionsByStoreId method\n";
echo "------------------------------------------------\n";

try {
    $count = $foodItemModel->countFoodSectionsByStoreId(1);
    echo "Count for store_id 1: " . $count . "\n";
} catch (Exception $e) {
    echo "Error calling countFoodSectionsByStoreId: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 4: Check if there are any sections for different store IDs
echo "Test 4: Checking sections for different store IDs\n";
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
    echo "Error getting store counts: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
