<?php
/**
 * Simple test script to check database connectivity and table existence
 */

require_once 'vendor/autoload.php';
require_once 'config/cors.php';

use Config\Database;

echo "=== Simple Database Test ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "✅ Database connection successful\n";
    
    // Test 1: Check if food_sections table exists
    echo "\nTest 1: Checking if food_sections table exists\n";
    $query = "SHOW TABLES LIKE 'food_sections'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists) {
        echo "✅ food_sections table exists\n";
    } else {
        echo "❌ food_sections table does not exist\n";
        exit;
    }
    
    // Test 2: Check table structure
    echo "\nTest 2: Checking table structure\n";
    $query = "DESCRIBE food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    // Test 3: Check if there's any data
    echo "\nTest 3: Checking for data\n";
    $query = "SELECT COUNT(*) as total FROM food_sections";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total sections: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        // Test 4: Get a sample record
        echo "\nTest 4: Getting sample record\n";
        $query = "SELECT * FROM food_sections LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $section = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Sample section:\n";
        foreach ($section as $key => $value) {
            echo "  - " . $key . ": " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
        
        // Test 5: Test the specific query that's failing
        echo "\nTest 5: Testing the failing query\n";
        $storeId = $section['store_id'];
        $limit = 10;
        $offset = 0;
        
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
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
