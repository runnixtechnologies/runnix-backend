<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

// Include necessary files
require_once '../config/Database.php';
require_once '../middleware/authMiddleware.php';
require_once '../controller/OrderController.php';

header('Content-Type: application/json');

use Controller\OrderController;
use function Middleware\authenticateRequest;

// Authenticate user
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Check if OrderController class exists
        if (!class_exists('Controller\OrderController')) {
            throw new Exception('OrderController class not found');
        }
        
        $orderController = new OrderController();
        
        // Get order ID from query parameters
        $orderId = $_GET['order_id'] ?? null;
        
        if (!$orderId) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Order ID is required."
            ]);
            exit;
        }
        
        // Validate order ID
        if (!is_numeric($orderId) || $orderId <= 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid order ID."
            ]);
            exit;
        }
        
        $response = $orderController->getOrderDetails($orderId, $user);
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log('Get order details error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving order details: " . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed. Only GET requests are supported."
    ]);
}
?>
