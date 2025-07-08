<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\DiscountController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Authenticate user
$user = authenticateRequest();
if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Get request payload
$data = json_decode(file_get_contents("php://input"), true) ?? [];

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Discount ID is required']);
    exit;
}

// Optional: enforce store ownership check
if ($user['store_id'] != $data['store_id']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: You do not own this discount']);
    exit;
}

// Update the discount
$controller = new DiscountController();
$response = $controller->updateDiscount($data['id'], $data);

echo json_encode($response);
?>