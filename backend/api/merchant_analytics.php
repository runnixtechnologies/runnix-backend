<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../middleware/authMiddleware.php';
require_once '../controller/AnalyticsController.php';

use Controller\AnalyticsController;
use Middleware;

try {
    // Authenticate the request
    $user = Middleware\authenticateRequest();
    
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
    
    $analyticsController = new AnalyticsController();
    
    // Handle different request methods
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
        
        // Get analytics overview/summary only
        $response = $analyticsController->getAnalyticsSummary($storeId);
        echo json_encode($response);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle POST request for analytics summary or specific queries
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid JSON data'
            ]);
            exit();
        }
        
        $action = $input['action'] ?? 'summary';
        
        switch ($action) {
            case 'summary':
                $response = $analyticsController->getAnalyticsSummary($storeId);
                echo json_encode($response);
                break;
                
            case 'comparison':
                $currentPeriod = $input['current_period'] ?? 'this_week';
                $previousPeriod = $input['previous_period'] ?? 'last_week';
                
                // This would require additional implementation in the controller
                http_response_code(501);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Comparison feature not yet implemented'
                ]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid action. Supported actions: summary'
                ]);
                break;
        }
        
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
