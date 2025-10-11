<?php
/**
 * Debug script to test store retrieval step by step
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Controller\StoreController;
use Model\Store;
use Config\Database;

echo "=== Debugging Store Retrieval ===\n\n";

// Test 1: Check database connection and stores table
echo "Test 1: Checking database and stores table\n";
echo "-------------------------------------------\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Check if stores table exists
    $query = "SHOW TABLES LIKE 'stores'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists) {
        echo "✅ stores table exists\n";
    } else {
        echo "❌ stores table does not exist\n";
        exit;
    }
    
    // Check stores table structure
    $query = "DESCRIBE stores";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Stores table structure:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Check if there are any stores
    $query = "SELECT COUNT(*) as total FROM stores";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total stores in database: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        $query = "SELECT id, user_id, store_name FROM stores LIMIT 3";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Sample stores:\n";
        foreach ($stores as $store) {
            echo "  - ID: " . $store['id'] . ", User ID: " . $store['user_id'] . ", Name: " . $store['store_name'] . "\n";
        }
    } else {
        echo "No stores found in database\n";
        exit;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit;
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 2: Test Store model directly
echo "Test 2: Testing Store model directly\n";
echo "------------------------------------\n";

try {
    $storeModel = new Store();
    $userId = $stores[0]['user_id']; // Use the first store's user_id
    
    echo "Testing getStoreByUserId with user_id: " . $userId . "\n";
    
    $store = $storeModel->getStoreByUserId($userId);
    
    if ($store) {
        echo "✅ Store found via model:\n";
        echo "  - ID: " . $store['id'] . "\n";
        echo "  - User ID: " . $store['user_id'] . "\n";
        echo "  - Store Name: " . $store['store_name'] . "\n";
        echo "  - All fields: " . json_encode($store) . "\n";
    } else {
        echo "❌ Store not found via model\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 3: Test StoreController
echo "Test 3: Testing StoreController\n";
echo "--------------------------------\n";

try {
    $storeController = new StoreController();
    
    echo "Testing getStoreByUserId via controller with user_id: " . $userId . "\n";
    
    $storeResponse = $storeController->getStoreByUserId($userId);
    
    echo "Controller response: " . json_encode($storeResponse) . "\n";
    
    if ($storeResponse['status'] === 'success') {
        echo "✅ Store found via controller:\n";
        $store = $storeResponse['store'];
        echo "  - ID: " . $store['id'] . "\n";
        echo "  - User ID: " . $store['user_id'] . "\n";
        echo "  - Store Name: " . $store['store_name'] . "\n";
    } else {
        echo "❌ Store not found via controller: " . $storeResponse['message'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test 4: Test with a non-existent user_id
echo "Test 4: Testing with non-existent user_id\n";
echo "----------------------------------------\n";

try {
    $nonExistentUserId = 99999;
    
    echo "Testing with non-existent user_id: " . $nonExistentUserId . "\n";
    
    $storeResponse = $storeController->getStoreByUserId($nonExistentUserId);
    
    echo "Controller response: " . json_encode($storeResponse) . "\n";
    
    if ($storeResponse['status'] === 'error') {
        echo "✅ Correctly returned error for non-existent user\n";
    } else {
        echo "❌ Unexpected response for non-existent user\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
