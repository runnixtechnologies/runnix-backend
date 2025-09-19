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
        error_log('Get merchant orders error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving orders."
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
