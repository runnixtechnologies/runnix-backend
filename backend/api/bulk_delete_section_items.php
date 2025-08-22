<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed. Use DELETE method']);
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

    // Create controller instance and call method
    $controller = new FoodItemController();
    $response = $controller->bulkDeleteSectionItems($data['item_ids'], $user);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
