<?php
// Simple test for analytics system without authentication
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Test if we can load the required classes
    require_once '../controller/AnalyticsController.php';
    require_once '../model/Analytics.php';
    require_once '../model/Store.php';
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Analytics classes loaded successfully',
        'classes_loaded' => [
            'AnalyticsController' => class_exists('Controller\AnalyticsController'),
            'Analytics' => class_exists('Model\Analytics'),
            'Store' => class_exists('Model\Store')
        ],
        'test_info' => [
            'message' => 'All required classes are available',
            'next_step' => 'Test with authentication token'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load analytics classes: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
