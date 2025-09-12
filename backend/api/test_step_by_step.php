<?php
// Step-by-step test for analytics system
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$steps = [];
$stepNumber = 1;

try {
    // Step 1: Test database connection
    $steps["step_{$stepNumber}"] = "Testing database connection...";
    require_once '../config/Database.php';
    use Config\Database;
    $db = new Database();
    $conn = $db->getConnection();
    $steps["step_{$stepNumber}_result"] = "SUCCESS - Database connected";
    $stepNumber++;

    // Step 2: Test Store model
    $steps["step_{$stepNumber}"] = "Testing Store model...";
    require_once '../model/Store.php';
    use Model\Store;
    $storeModel = new Store();
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
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed at step ' . $stepNumber,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'completed_steps' => $steps
    ], JSON_PRETTY_PRINT);
}
