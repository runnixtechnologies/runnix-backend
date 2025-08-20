<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/Database.php';
require_once '../controller/FoodItemController.php';
require_once '../middleware/authMiddleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format']);
        exit;
    }

    // Validate required fields
    if (!isset($data['item_ids']) || !is_array($data['item_ids']) || empty($data['item_ids'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Item IDs array is required and must not be empty']);
        exit;
    }

    // Validate that all item_ids are numeric
    foreach ($data['item_ids'] as $itemId) {
        if (!is_numeric($itemId)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'All item IDs must be numeric']);
            exit;
        }
    }

    // Authenticate user
    $user = authenticateRequest();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit;
    }

    // Create controller instance and call method
    $controller = new FoodItemController();
    $response = $controller->bulkDeactivateSectionItems($data['item_ids'], $user);

    // Set appropriate HTTP status code
    $statusCode = isset($response['status']) && $response['status'] === 'success' ? 200 : 400;
    http_response_code($statusCode);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>
