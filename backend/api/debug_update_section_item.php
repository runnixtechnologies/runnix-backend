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
    // Step 1: Test if we can load the controller
    echo json_encode([
        'step' => '1',
        'status' => 'success',
        'message' => 'Starting debug process'
    ]);
    exit;

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON format']);
        exit;
    }

    // Validate required fields
    if (!isset($data['item_id']) || empty($data['item_id'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
        exit;
    }

    // Authenticate user
    $user = authenticateRequest();

    // Create controller instance and call method
    $controller = new FoodItemController();
    $response = $controller->updateSectionItem($data['item_id'], $data, $user);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
