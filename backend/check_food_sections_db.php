<?php
/**
 * Simple script to check food_sections database table
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Config\Database;

echo "=== Checking Food Sections Database ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if food_sections table exists
    echo "\n1. Checking if food_sections table exists...\n";
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
    
    // Check stores table
    echo "\n2. Checking stores table...\n";
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
        
        $storeId = $stores[0]['id'];
        
        // Check food_sections for this store
        echo "\n3. Checking food_sections for store ID " . $storeId . "...\n";
        $query = "SELECT COUNT(*) as total FROM food_sections WHERE store_id = :store_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':store_id' => $storeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Total food sections for store " . $storeId . ": " . $result['total'] . "\n";
        
        if ($result['total'] > 0) {
            $query = "SELECT id, store_id, section_name, status FROM food_sections WHERE store_id = :store_id LIMIT 3";
            $stmt = $conn->prepare($query);
            $stmt->execute([':store_id' => $storeId]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Sample sections:\n";
            foreach ($sections as $section) {
                echo "  - ID: " . $section['id'] . ", Store: " . $section['store_id'] . ", Name: " . $section['section_name'] . ", Status: " . $section['status'] . "\n";
            }
        } else {
            echo "No food sections found for this store. Creating a test section...\n";
            
            $insertQuery = "INSERT INTO food_sections (store_id, section_name, max_quantity, required, price, status) 
                           VALUES (:store_id, 'Test Section', 5, 1, 2.50, 'active')";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->execute([':store_id' => $storeId]);
            
            echo "✅ Test food section created\n";
            
            // Verify it was created
            $query = "SELECT id, store_id, section_name, status FROM food_sections WHERE store_id = :store_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':store_id' => $storeId]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Now we have " . count($sections) . " sections:\n";
            foreach ($sections as $section) {
                echo "  - ID: " . $section['id'] . ", Store: " . $section['store_id'] . ", Name: " . $section['section_name'] . ", Status: " . $section['status'] . "\n";
            }
        }
    } else {
        echo "❌ No stores found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Check Complete ===\n";
?>
