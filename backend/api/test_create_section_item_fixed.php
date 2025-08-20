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
    if (!isset($data['section_id']) || empty($data['section_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
        exit;
    }

    if (!isset($data['name']) || empty($data['name'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Item name is required']);
        exit;
    }

    if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Valid price is required (non-negative number)']);
        exit;
    }

    // Authenticate user
    $user = authenticateRequest();

    // Create controller instance and call method
    $controller = new FoodItemController();
    $response = $controller->createSectionItem($data, $user);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
