<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force error logging to a specific file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\OrderController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Authenticate user
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
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
        
        $response = $orderController->getDeliveryTracking($orderId, $user);
        echo json_encode($response);
        
    } catch (Exception $e) {
        $errorMessage = 'Get delivery tracking error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
        error_log($errorMessage, 3, __DIR__ . '/php-error.log');
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving delivery tracking."
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
