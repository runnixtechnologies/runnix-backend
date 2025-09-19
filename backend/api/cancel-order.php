<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $orderController = new OrderController();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (empty($data['order_id'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Order ID is required."
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
