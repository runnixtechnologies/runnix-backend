<?php
/**
 * Test script for the get food section by id endpoint
 * This script tests the endpoint without authentication to check basic functionality
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Config\Database;

echo "=== Testing Food Section Endpoint ===\n\n";

$foodItemModel = new FoodItem();
$db = new Database();
$conn = $db->getConnection();

// Test 1: Check if food_sections table exists
echo "Test 1: Checking if food_sections table exists\n";
echo "-----------------------------------------------\n";

try {
    $query = "SHOW TABLES LIKE 'food_sections'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists) {
        echo "✅ food_sections table exists\n";
    } else {
        echo "❌ food_sections table does not exist\n";
        echo "Please run the migration: php migrations/create_food_sections_table.php\n";
        exit;
    }
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check table structure
echo "\nTest 2: Checking food_sections table structure\n";
echo "-----------------------------------------------\n";

try {
    $query = "DESCRIBE food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table structure:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error checking table structure: " . $e->getMessage() . "\n";
}

// Test 3: Check if there are any sections in the database
echo "\nTest 3: Checking for existing sections\n";
echo "---------------------------------------\n";

try {
    $query = "SELECT COUNT(*) as total FROM food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total sections in database: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        $query = "SELECT id, store_id, section_name, status FROM food_sections LIMIT 5";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample sections:\n";
        foreach ($sections as $section) {
            echo "  - ID: " . $section['id'] . ", Store: " . $section['store_id'] . ", Name: " . $section['section_name'] . ", Status: " . $section['status'] . "\n";
        }
    } else {
        echo "No sections found. You may need to create some test data.\n";
    }
} catch (Exception $e) {
    echo "Error checking sections: " . $e->getMessage() . "\n";
}

// Test 4: Test the getFoodSectionById method directly
echo "\nTest 4: Testing getFoodSectionById method\n";
echo "------------------------------------------\n";

if ($result['total'] > 0) {
    try {
        // Get the first section ID
        $query = "SELECT id FROM food_sections LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $firstSection = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($firstSection) {
            $sectionId = $firstSection['id'];
            echo "Testing with section ID: " . $sectionId . "\n";
            
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
        }
    } catch (Exception $e) {
        echo "Error testing getFoodSectionById: " . $e->getMessage() . "\n";
    }
} else {
    echo "Skipping method test - no sections available\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the actual API endpoint:\n";
echo "1. Ensure you have a valid merchant JWT token\n";
echo "2. Make a GET request to: /api/get_foodsectionby_id.php?id=<section_id>\n";
echo "3. Include the Authorization header: Bearer <your_jwt_token>\n";
?>
