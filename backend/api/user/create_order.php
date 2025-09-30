<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';

// Route-level logging to backend/php-error.log
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

use Controller\OrderController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Check authentication
$user = authenticateRequest();

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['store_id']) || !isset($data['items']) || !is_array($data['items'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Store ID and items are required"]);
    exit;
}

if (empty($data['items'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "At least one item is required"]);
    exit;
}

// Validate each item
foreach ($data['items'] as $item) {
    if (!isset($item['item_id']) || !isset($item['quantity'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Each item must have item_id and quantity"]);
        exit;
    }
    
    if ($item['quantity'] < 1) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Item quantity must be at least 1"]);
        exit;
    }
}

// Call controller
$controller = new OrderController();
$response = $controller->createCustomerOrder($user, $data);

echo json_encode($response);
