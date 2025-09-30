<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';

use Model\Store;

echo "=== Debugging Store Query ===\n\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=u232647434_db", "u232647434_runnix", "Runnix@2025");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test 1: Check if store_types has status column and values
    echo "1. Checking store_types table:\n";
    $sql1 = "SELECT id, name, status FROM store_types WHERE id IN (1,2,3)";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result1, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Check stores table structure
    echo "2. Checking stores table structure:\n";
    $sql2 = "DESCRIBE stores";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result2, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Check stores data
    echo "3. Checking stores data:\n";
    $sql3 = "SELECT id, store_name, store_type_id FROM stores WHERE store_type_id IN (1,2,3)";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    $result3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result3, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 4: Test the exact query from the model
    echo "4. Testing exact model query for store_type_id = 1:\n";
    $sql4 = "SELECT s.*, st.name AS store_type_name, 
                    COALESCE(AVG(r.rating), 0) AS rating,
                    COUNT(r.id) AS review_count,
                    ss.is_online
             FROM stores s 
             LEFT JOIN store_types st ON s.store_type_id = st.id 
             LEFT JOIN reviews r ON s.id = r.store_id AND r.status = 1
             LEFT JOIN store_status ss ON s.id = ss.store_id
             WHERE st.status = 1 AND s.store_type_id = 1
             GROUP BY s.id
             ORDER BY rating DESC, review_count DESC
             LIMIT 20";
    
    $stmt4 = $pdo->prepare($sql4);
    $stmt4->execute();
    $result4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($result4) . "\n";
    echo json_encode($result4, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 5: Test without store_types status filter
    echo "5. Testing without store_types status filter:\n";
    $sql5 = "SELECT s.*, st.name AS store_type_name, 
                    COALESCE(AVG(r.rating), 0) AS rating,
                    COUNT(r.id) AS review_count,
                    ss.is_online
             FROM stores s 
             LEFT JOIN store_types st ON s.store_type_id = st.id 
             LEFT JOIN reviews r ON s.id = r.store_id AND r.status = 1
             LEFT JOIN store_status ss ON s.id = ss.store_id
             WHERE s.store_type_id = 1
             GROUP BY s.id
             ORDER BY rating DESC, review_count DESC
             LIMIT 20";
    
    $stmt5 = $pdo->prepare($sql5);
    $stmt5->execute();
    $result5 = $stmt5->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($result5) . "\n";
    echo json_encode($result5, JSON_PRETTY_PRINT) . "\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
