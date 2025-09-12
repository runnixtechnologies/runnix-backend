<?php
// Test merchant metrics without authentication
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    require_once '../controller/AnalyticsController.php';
    
    use Controller\AnalyticsController;
    
    // Test with a hardcoded store ID (you'll need to replace this with a real store ID)
    $testStoreId = 1; // Change this to an actual store ID from your database
    
    $analyticsController = new AnalyticsController();
    
    // Test the metrics endpoint
    $response = $analyticsController->getMerchantMetrics($testStoreId, 'this_week');
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
