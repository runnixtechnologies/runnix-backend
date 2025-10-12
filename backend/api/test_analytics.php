<?php
// Test script for analytics system
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../controller/AnalyticsController.php';
require_once '../model/Store.php';

use Controller\AnalyticsController;
use Model\Store;

try {
    $storeModel = new Store();
    $analyticsController = new AnalyticsController();
    
    // Get a sample store for testing
    $stores = $storeModel->getAllStores();
    
    if (empty($stores)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No stores found for testing'
        ]);
        exit();
    }
    
    $testStore = $stores[0];
    $storeId = $testStore['id'];
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Analytics system test',
        'test_store' => [
            'id' => $testStore['id'],
            'name' => $testStore['store_name'],
            'type' => $testStore['store_type_name'] ?? 'Unknown'
        ],
        'available_endpoints' => [
            'GET /api/merchant_analytics.php - Analytics overview/summary',
            'GET /api/merchant_metrics.php?period=this_week - Core metrics (dashboard cards)',
            'GET /api/merchant_orders_analytics.php?period=this_month - Orders analytics (bar chart)',
            'GET /api/merchant_top_items.php?period=this_month&limit=5 - Top performing items',
            'GET /api/merchant_metrics.php?period=custom&start_date=2024-01-01&end_date=2024-01-31 - Custom date range metrics',
            'POST /api/merchant_analytics.php with {"action": "summary"} - Growth comparison'
        ],
        'supported_periods' => [
            'today', 'yesterday', 'this_week', 'last_week', 
            'this_month', 'last_month', 'this_year', 'all_time', 'custom'
        ],
        'sample_response_structure' => [
            'status' => 'success',
            'data' => [
                'store_info' => [
                    'id' => 'int',
                    'name' => 'string',
                    'type' => 'food|non_food',
                    'store_type' => 'string'
                ],
                'date_range' => [
                    'period' => 'string',
                    'start_date' => 'YYYY-MM-DD HH:MM:SS',
                    'end_date' => 'YYYY-MM-DD HH:MM:SS'
                ],
                'metrics' => [
                    'total_revenue' => 'float',
                    'total_orders' => 'int',
                    'total_profile_visits' => 'int',
                    'total_users' => 'int',
                    'avg_response_time' => 'float',
                    'total_rating' => 'float'
                ],
                'orders_analytics' => [
                    [
                        'date' => 'YYYY-MM-DD',
                        'orders' => 'int',
                        'revenue' => 'float'
                    ]
                ],
                'top_performing_items' => [
                    [
                        'id' => 'int',
                        'name' => 'string',
                        'price' => 'float',
                        'photo' => 'string|null',
                        'short_description' => 'string|null',
                        'order_count' => 'int',
                        'total_quantity' => 'int',
                        'total_revenue' => 'float'
                    ]
                ]
            ]
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Test failed: ' . $e->getMessage()
    ]);
}
