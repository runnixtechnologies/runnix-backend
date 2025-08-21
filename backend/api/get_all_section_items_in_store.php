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
    // Get pagination parameters from URL
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

    // Validate pagination parameters
    if ($page < 1) {
        $page = 1;
    }
    if ($limit < 1 || $limit > 50) {
        $limit = 10; // Default to 10 if invalid
    }

    // Authenticate user
    $user = authenticateRequest();

    // Create controller instance and call method
    $controller = new FoodItemController();
    $response = $controller->getAllSectionItemsInStore($user, $page, $limit);

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
