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

// Check if user is a merchant (store owner)
if ($user['role'] !== 'merchant') {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Access denied. Only store owners can view orders."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        // Check if OrderController class exists
        if (!class_exists('Controller\OrderController')) {
            throw new Exception('OrderController class not found');
        }
        
        $orderController = new OrderController();
        
        // Get query parameters
        $status = $_GET['status'] ?? null; // pending, accepted, in_transit, completed
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        
        // Validate status if provided
        if ($status && !in_array($status, ['pending', 'accepted', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'cancelled'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid status. Valid statuses: pending, accepted, preparing, ready_for_pickup, in_transit, delivered, cancelled"
            ]);
            exit;
        }
        
        // Map UI status to database status
        $statusMapping = [
            'pending' => 'pending',
            'accepted' => 'accepted',
            'in_transit' => 'in_transit',
            'completed' => 'delivered'
        ];
        
        $dbStatus = $status ? ($statusMapping[$status] ?? $status) : null;
        
        $response = $orderController->getMerchantOrders($user, $dbStatus, $page, $limit);
        echo json_encode($response);
        
    } catch (Exception $e) {
        $errorMessage = 'Get merchant orders error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
        error_log($errorMessage, 3, __DIR__ . '/php-error.log');
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving orders: " . $e->getMessage()
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
