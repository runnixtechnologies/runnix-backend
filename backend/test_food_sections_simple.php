<?php
/**
 * Simple test to check food_sections table
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Config\Database;

echo "=== Testing Food Sections Table ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Test 1: Check if food_sections table exists
    echo "\n1. Checking if food_sections table exists...\n";
    $query = "SHOW TABLES LIKE 'food_sections'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists) {
        echo "✅ food_sections table exists\n";
    } else {
        echo "❌ food_sections table does not exist\n";
        echo "Creating table...\n";
        
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
    
    // Test 2: Check table structure
    echo "\n2. Checking table structure...\n";
    $query = "DESCRIBE food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columns found:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Test 3: Check if there are any stores
    echo "\n3. Checking stores...\n";
    $query = "SELECT COUNT(*) as total FROM stores";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total stores: " . $result['total'] . "\n";
    
    if ($result['total'] == 0) {
        echo "❌ No stores found!\n";
        exit;
    }
    
    // Get first store
    $query = "SELECT id, user_id, store_name FROM stores LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $store = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Using store: ID=" . $store['id'] . ", Name=" . $store['store_name'] . "\n";
    
    // Test 4: Check food sections for this store
    echo "\n4. Checking food sections for store " . $store['id'] . "...\n";
    
    // Count all sections (regardless of status)
    $query = "SELECT COUNT(*) as total FROM food_sections WHERE store_id = :store_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':store_id' => $store['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total sections (all statuses): " . $result['total'] . "\n";
    
    // Count active sections only
    $query = "SELECT COUNT(*) as total FROM food_sections WHERE store_id = :store_id AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([':store_id' => $store['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Active sections only: " . $result['total'] . "\n";
    
    // Show all sections with their status
    $query = "SELECT id, section_name, status, price, created_at FROM food_sections WHERE store_id = :store_id ORDER BY id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':store_id' => $store['id']]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($sections)) {
        echo "\nAll sections for this store:\n";
        foreach ($sections as $section) {
            echo "  - ID: " . $section['id'] . ", Name: " . $section['section_name'] . ", Status: " . $section['status'] . ", Price: " . $section['price'] . "\n";
        }
    } else {
        echo "No sections found for this store. Creating a test section...\n";
        
        $insertQuery = "INSERT INTO food_sections (store_id, section_name, max_quantity, required, price, status) 
                       VALUES (:store_id, 'Test Section', 5, 1, 2.50, 'active')";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->execute([':store_id' => $store['id']]);
        
        echo "✅ Test food section created\n";
        
        // Verify it was created
        $query = "SELECT id, section_name, status, price FROM food_sections WHERE store_id = :store_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':store_id' => $store['id']]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Now we have " . count($sections) . " sections:\n";
        foreach ($sections as $section) {
            echo "  - ID: " . $section['id'] . ", Name: " . $section['section_name'] . ", Status: " . $section['status'] . ", Price: " . $section['price'] . "\n";
        }
    }
    
    // Test 5: Test the exact query from the model
    echo "\n5. Testing the exact query from the model...\n";
    $storeId = $store['id'];
    $limit = 10;
    $offset = 0;
    
    $query = "SELECT fs.id, fs.store_id, fs.section_name as name, fs.max_quantity, fs.required, fs.price, fs.status, fs.created_at, fs.updated_at
              FROM food_sections fs
              WHERE fs.store_id = :store_id 
              AND fs.status = 'active'
              ORDER BY fs.id DESC
              LIMIT :limit OFFSET :offset";
    
    echo "Query: " . $query . "\n";
    echo "Parameters: store_id=" . $storeId . ", limit=" . $limit . ", offset=" . $offset . "\n";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':store_id', $storeId, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
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
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
