<?php
// Minimal analytics test
header('Content-Type: application/json');

try {
    // Test 1: Basic PHP
    $test1 = 'PHP working';
    
    // Test 2: Database connection
    require_once '../config/Database.php';
    use Config\Database;
    $db = new Database();
    $conn = $db->getConnection();
    $test2 = 'Database connected';
    
    // Test 3: Store model
    require_once '../model/Store.php';
    use Model\Store;
    $storeModel = new Store();
    $test3 = 'Store model loaded';
    
    // Test 4: Get stores count
    $stores = $storeModel->getAllStores();
    $test4 = 'Found ' . count($stores) . ' stores';
    
    // Test 5: Analytics model
    require_once '../model/Analytics.php';
    use Model\Analytics;
    $analyticsModel = new Analytics();
    $test5 = 'Analytics model loaded';
    
    echo json_encode([
        'status' => 'success',
        'message' => 'All tests passed',
        'tests' => [
            'php' => $test1,
            'database' => $test2,
            'store_model' => $test3,
            'stores_count' => $test4,
            'analytics_model' => $test5
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
