<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\AnalyticsController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    // Authenticate the request
    $user = authenticateRequest();
    
    // Check if user is a merchant
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Access denied. Only merchants can access analytics.'
        ]);
        exit();
    }
    
    // Get store ID from user data
    $storeId = $user['store_id'] ?? null;
    if (!$storeId) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Store ID not found. Please ensure your merchant account is properly set up.'
        ]);
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $analyticsController = new AnalyticsController();
        
        // Get query parameters
        $dateRange = $_GET['period'] ?? 'this_week';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Validate date range parameter
        $allowedPeriods = ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year', 'all_time', 'custom'];
        if (!in_array($dateRange, $allowedPeriods)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid period. Allowed values: ' . implode(', ', $allowedPeriods)
            ]);
            exit();
        }
        
        // Validate custom date range
        if ($dateRange === 'custom') {
            if (!$startDate || !$endDate) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Start date and end date are required for custom period'
                ]);
                exit();
            }
            
            // Validate date format
            if (!strtotime($startDate) || !strtotime($endDate)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid date format. Use YYYY-MM-DD format'
                ]);
                exit();
            }
            
            // Validate date range
            if (strtotime($startDate) > strtotime($endDate)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Start date cannot be after end date'
                ]);
                exit();
            }
        }
        
        // Get orders analytics data only (for bar chart)
        $response = $analyticsController->getOrdersAnalytics($storeId, $dateRange, $startDate, $endDate);
        echo json_encode($response);
        
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Merchant Orders Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
