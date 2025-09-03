<?php
/**
 * Simple test script to check food sections functionality
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Model\FoodItem;
use Config\Database;

echo "=== Testing Food Sections Functionality ===\n\n";

// Test 1: Check database connection
echo "Test 1: Database Connection\n";
echo "----------------------------\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check if food_sections table exists
echo "\nTest 2: Check food_sections table\n";
echo "----------------------------------\n";

try {
    $query = "SHOW TABLES LIKE 'food_sections'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists) {
        echo "✅ food_sections table exists\n";
    } else {
        echo "❌ food_sections table does not exist\n";
        echo "Creating food_sections table...\n";
        
        $createTableQuery = "CREATE TABLE IF NOT EXISTS food_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            store_id INT NOT NULL,
            section_name VARCHAR(255) NOT NULL,
            max_quantity INT DEFAULT 0,
            required BOOLEAN DEFAULT FALSE,
            price DECIMAL(10,2) DEFAULT 0.00,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_store_id (store_id),
            INDEX idx_status (status)
        )";
        
        $stmt = $conn->prepare($createTableQuery);
        $stmt->execute();
        echo "✅ food_sections table created\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking/creating table: " . $e->getMessage() . "\n";
}

// Test 3: Check if there are any stores
echo "\nTest 3: Check stores table\n";
echo "---------------------------\n";

try {
    $query = "SELECT COUNT(*) as total FROM stores";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total stores: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        $query = "SELECT id, user_id, store_name FROM stores LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample stores:\n";
        foreach ($stores as $store) {
            echo "  - ID: " . $store['id'] . ", User: " . $store['user_id'] . ", Name: " . $store['store_name'] . "\n";
        }
    } else {
        echo "No stores found. You need to create a store first.\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error checking stores: " . $e->getMessage() . "\n";
    exit;
}

// Test 4: Check if there are any food sections
echo "\nTest 4: Check food_sections data\n";
echo "---------------------------------\n";

try {
    $query = "SELECT COUNT(*) as total FROM food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total food sections: " . $result['total'] . "\n";
    
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
        echo "No food sections found. Creating a test section...\n";
        
        $storeId = $stores[0]['id'];
        $insertQuery = "INSERT INTO food_sections (store_id, section_name, max_quantity, required, price, status) 
                       VALUES (:store_id, 'Test Section', 5, 1, 2.50, 'active')";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->execute([':store_id' => $storeId]);
        
        echo "✅ Test food section created for store ID: " . $storeId . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking/creating food sections: " . $e->getMessage() . "\n";
}

// Test 5: Test the model method directly
echo "\nTest 5: Test model method\n";
echo "--------------------------\n";

try {
    $foodItemModel = new FoodItem();
    $storeId = $stores[0]['id'];
    
    echo "Testing getAllFoodSectionsByStoreId with store ID: " . $storeId . "\n";
    
    $sections = $foodItemModel->getAllFoodSectionsByStoreId($storeId, 10, 0);
    
    if (is_array($sections)) {
        echo "✅ Model method returned " . count($sections) . " sections\n";
        if (!empty($sections)) {
            echo "First section: " . json_encode($sections[0]) . "\n";
        }
    } else {
        echo "❌ Model method returned: " . gettype($sections) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing model method: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
