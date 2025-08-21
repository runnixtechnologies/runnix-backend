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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get item ID from URL parameter
    $itemId = isset($_GET['id']) ? $_GET['id'] : null;

    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
        exit;
    }

    // Authenticate user
    $user = authenticateRequest();

    // Create controller instance and call method
    $controller = new FoodItemController();
    $response = $controller->getSectionItemById($itemId, $user);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
