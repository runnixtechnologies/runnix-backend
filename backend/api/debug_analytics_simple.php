<?php
// Simple analytics debug without authentication
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

try {
    require_once '../config/Database.php';
    require_once '../model/Analytics.php';
    require_once '../model/Store.php';
    
    use Config\Database;
    use Model\Analytics;
    use Model\Store;
    
    $debug = [];
    
    // Test database connection
    $db = new Database();
    $conn = $db->getConnection();
    $debug['database'] = 'Connected successfully';
    
    // Test getting stores
    $storeModel = new Store();
    $stores = $storeModel->getAllStores();
    $debug['stores_count'] = count($stores);
    
    if (empty($stores)) {
        $debug['error'] = 'No stores found in database';
        echo json_encode($debug);
        exit;
    }
    
    $testStore = $stores[0];
    $debug['test_store'] = [
        'id' => $testStore['id'],
        'name' => $testStore['store_name'],
        'type' => $testStore['store_type_name'] ?? 'Unknown'
    ];
    
    // Test analytics model
    $analyticsModel = new Analytics();
    $debug['analytics_model'] = 'Created successfully';
    
    // Test date range
    $dateRange = [
        'start_date' => date('Y-m-d H:i:s', strtotime('-7 days')),
        'end_date' => date('Y-m-d H:i:s')
    ];
    $debug['date_range'] = $dateRange;
    
    // Test each analytics method
    try {
        $revenue = $analyticsModel->getTotalRevenue($testStore['id'], $dateRange);
        $debug['revenue'] = $revenue;
    } catch (Exception $e) {
        $debug['revenue_error'] = $e->getMessage();
    }
    
    try {
        $orders = $analyticsModel->getTotalOrders($testStore['id'], $dateRange);
        $debug['orders'] = $orders;
    } catch (Exception $e) {
        $debug['orders_error'] = $e->getMessage();
    }
    
    try {
        $users = $analyticsModel->getTotalUsers($testStore['id'], $dateRange);
        $debug['users'] = $users;
    } catch (Exception $e) {
        $debug['users_error'] = $e->getMessage();
    }
    
    try {
        $visits = $analyticsModel->getTotalProfileVisits($testStore['id'], $dateRange);
        $debug['visits'] = $visits;
    } catch (Exception $e) {
        $debug['visits_error'] = $e->getMessage();
    }
    
    try {
        $responseTime = $analyticsModel->getAvgResponseTime($testStore['id'], $dateRange);
        $debug['response_time'] = $responseTime;
    } catch (Exception $e) {
        $debug['response_time_error'] = $e->getMessage();
    }
    
    try {
        $rating = $analyticsModel->getTotalRating($testStore['id'], $dateRange);
        $debug['rating'] = $rating;
    } catch (Exception $e) {
        $debug['rating_error'] = $e->getMessage();
    }
    
    // Test if store has food items
    try {
        $hasFoodItems = $analyticsModel->hasFoodItems($testStore['id']);
        $debug['has_food_items'] = $hasFoodItems;
    } catch (Exception $e) {
        $debug['has_food_items_error'] = $e->getMessage();
    }
    
    echo json_encode($debug, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Debug analytics simple error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
