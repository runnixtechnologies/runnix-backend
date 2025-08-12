<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Config\Database;

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Test 1: Check if food_sides table exists
    $tableQuery = "SHOW TABLES LIKE 'food_sides'";
    $tableStmt = $conn->prepare($tableQuery);
    $tableStmt->execute();
    $tableExists = $tableStmt->rowCount() > 0;
    
    // Test 2: Get total count of food sides
    $countQuery = "SELECT COUNT(*) as total FROM food_sides";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute();
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC);
    
    // Test 3: Get first 5 food sides
    $sampleQuery = "SELECT id, name, store_id FROM food_sides LIMIT 5";
    $sampleStmt = $conn->prepare($sampleQuery);
    $sampleStmt->execute();
    $sampleData = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test 4: Check specific ID 7
    $specificQuery = "SELECT * FROM food_sides WHERE id = 7";
    $specificStmt = $conn->prepare($specificQuery);
    $specificStmt->execute();
    $specificData = $specificStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'table_exists' => $tableExists,
            'total_food_sides' => $totalCount['total'],
            'sample_data' => $sampleData,
            'id_7_exists' => $specificData ? true : false,
            'id_7_data' => $specificData
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
