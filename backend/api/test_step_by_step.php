<?php
// Step-by-step test for analytics system with detailed error logging
// This is for development/testing purposes only

// Enable detailed error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Log the start of the test
error_log("[" . date('Y-m-d H:i:s') . "] Starting step-by-step analytics test");

$steps = [];
$stepNumber = 1;

try {
    // Step 1: Test database connection
    $steps["step_{$stepNumber}"] = "Testing database connection...";
    error_log("[" . date('Y-m-d H:i:s') . "] Step 1: Testing database connection");
    
    require_once '../config/Database.php';
    error_log("[" . date('Y-m-d H:i:s') . "] Database.php loaded successfully");
    
    use Config\Database;
    error_log("[" . date('Y-m-d H:i:s') . "] Database class imported");
    
    $db = new Database();
    error_log("[" . date('Y-m-d H:i:s') . "] Database instance created");
    
    $conn = $db->getConnection();
    error_log("[" . date('Y-m-d H:i:s') . "] Database connection obtained");
    
    $steps["step_{$stepNumber}_result"] = "SUCCESS - Database connected";
    $stepNumber++;

    // Step 2: Test Store model
    $steps["step_{$stepNumber}"] = "Testing Store model...";
    error_log("[" . date('Y-m-d H:i:s') . "] Step 2: Testing Store model");
    
    require_once '../model/Store.php';
    error_log("[" . date('Y-m-d H:i:s') . "] Store.php loaded successfully");
    
    use Model\Store;
    error_log("[" . date('Y-m-d H:i:s') . "] Store class imported");
    
    $storeModel = new Store();
    error_log("[" . date('Y-m-d H:i:s') . "] Store model instance created");
    
    $steps["step_{$stepNumber}_result"] = "SUCCESS - Store model loaded";
    $stepNumber++;

    // Step 3: Test getting stores
    $steps["step_{$stepNumber}"] = "Testing get all stores...";
    $stores = $storeModel->getAllStores();
    $steps["step_{$stepNumber}_result"] = "SUCCESS - Found " . count($stores) . " stores";
    if (!empty($stores)) {
        $steps["sample_store"] = $stores[0];
    }
    $stepNumber++;

    // Step 4: Test Analytics model
    $steps["step_{$stepNumber}"] = "Testing Analytics model...";
    require_once '../model/Analytics.php';
    use Model\Analytics;
    $analyticsModel = new Analytics();
    $steps["step_{$stepNumber}_result"] = "SUCCESS - Analytics model loaded";
    $stepNumber++;

    // Step 5: Test Analytics controller
    $steps["step_{$stepNumber}"] = "Testing Analytics controller...";
    require_once '../controller/AnalyticsController.php';
    use Controller\AnalyticsController;
    $analyticsController = new AnalyticsController();
    $steps["step_{$stepNumber}_result"] = "SUCCESS - Analytics controller loaded";
    $stepNumber++;

    // Step 6: Test a simple analytics query
    if (!empty($stores)) {
        $steps["step_{$stepNumber}"] = "Testing analytics query...";
        $storeId = $stores[0]['id'];
        $dateRange = [
            'start_date' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'end_date' => date('Y-m-d H:i:s')
        ];
        
        $revenue = $analyticsModel->getTotalRevenue($storeId, $dateRange);
        $steps["step_{$stepNumber}_result"] = "SUCCESS - Revenue query returned: " . $revenue;
        $stepNumber++;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'All steps completed successfully',
        'steps' => $steps,
        'total_steps' => $stepNumber - 1
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Exception caught: " . $e->getMessage());
    error_log("[" . date('Y-m-d H:i:s') . "] Exception file: " . $e->getFile() . " line: " . $e->getLine());
    error_log("[" . date('Y-m-d H:i:s') . "] Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed at step ' . $stepNumber,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'completed_steps' => $steps
    ], JSON_PRETTY_PRINT);
}
