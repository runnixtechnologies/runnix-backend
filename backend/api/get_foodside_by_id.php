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

// Debug: Log all GET parameters
error_log("get_foodside_by_id API: GET parameters: " . json_encode($_GET));

// Validate input
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    error_log("get_foodside_by_id API: Invalid ID parameter: " . ($_GET['id'] ?? 'not set'));
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid food side ID is required']);
    exit;
}

$foodSideId = (int)$_GET['id'];
error_log("get_foodside_by_id API: Processing food side ID: $foodSideId");

$user = authenticateRequest();
error_log("get_foodside_by_id API: User authenticated: " . json_encode($user));

$controller = new FoodItemController();
$response = $controller->getFoodSideById($foodSideId, $user);
echo json_encode($response);
