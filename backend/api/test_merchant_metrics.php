<?php
// Test merchant metrics without authentication with detailed error logging
// This is for development/testing purposes only

// Enable detailed error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Log the start of the test
error_log("[" . date('Y-m-d H:i:s') . "] Starting merchant metrics test");

try {
    error_log("[" . date('Y-m-d H:i:s') . "] Loading AnalyticsController");
    require_once '../controller/AnalyticsController.php';
    error_log("[" . date('Y-m-d H:i:s') . "] AnalyticsController.php loaded successfully");
    
    use Controller\AnalyticsController;
    error_log("[" . date('Y-m-d H:i:s') . "] AnalyticsController class imported");
    
    // Test with a hardcoded store ID (you'll need to replace this with a real store ID)
    $testStoreId = 1; // Change this to an actual store ID from your database
    error_log("[" . date('Y-m-d H:i:s') . "] Using test store ID: " . $testStoreId);
    
    $analyticsController = new AnalyticsController();
    error_log("[" . date('Y-m-d H:i:s') . "] AnalyticsController instance created");
    
    // Test the metrics endpoint
    error_log("[" . date('Y-m-d H:i:s') . "] Calling getMerchantMetrics");
    $response = $analyticsController->getMerchantMetrics($testStoreId, 'this_week');
    error_log("[" . date('Y-m-d H:i:s') . "] getMerchantMetrics completed successfully");
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Exception caught: " . $e->getMessage());
    error_log("[" . date('Y-m-d H:i:s') . "] Exception file: " . $e->getFile() . " line: " . $e->getLine());
    error_log("[" . date('Y-m-d H:i:s') . "] Exception trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
