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
        
        // Validate required fields
        if (empty($data['order_id']) || empty($data['status'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Order ID and status are required."
            ]);
            exit;
        }
        
        // Validate order ID
        if (!is_numeric($data['order_id']) || $data['order_id'] <= 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid order ID."
            ]);
            exit;
        }
        
        // Validate status
        $validStatuses = ['pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled'];
        if (!in_array($data['status'], $validStatuses)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid status. Valid statuses: " . implode(', ', $validStatuses)
            ]);
            exit;
        }
        
        $orderId = $data['order_id'];
        $status = $data['status'];
        $reason = $data['reason'] ?? null;
        $notes = $data['notes'] ?? null;
        
        $response = $orderController->updateOrderStatus($orderId, $status, $user, $reason, $notes);
        echo json_encode($response);
        
    } catch (Exception $e) {
        $errorMessage = 'Update order status error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
        error_log($errorMessage, 3, __DIR__ . '/php-error.log');
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while updating order status."
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
