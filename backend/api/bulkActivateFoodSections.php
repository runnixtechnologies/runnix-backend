<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid or missing food section IDs"
    ]);
    exit;
}

try {
    $user = authenticateRequest();
    $controller = new FoodItemController();
    $response = $controller->bulkActivateFoodSections($data['ids'], $user);
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Internal server error: " . $e->getMessage()
    ]);
}
