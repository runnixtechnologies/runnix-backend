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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $orderController = new OrderController();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required field: order_id (ONLY required parameter)
        if (empty($data['order_id'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Order ID is required."
            ]);
            exit;
        }
        
        // Validate order_id format
        if (!is_numeric($data['order_id']) || $data['order_id'] <= 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid order ID. Must be a positive integer."
            ]);
            exit;
        }
        
        $orderId = $data['order_id'];
        $reason = $data['reason'] ?? null;
        
        $response = $orderController->cancelOrder($orderId, $user, $reason);
        echo json_encode($response);
        
    } catch (Exception $e) {
        $errorMessage = 'Cancel order error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
        error_log($errorMessage, 3, __DIR__ . '/php-error.log');
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while cancelling order."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed. Only POST requests are supported."
    ]);
}
?>
