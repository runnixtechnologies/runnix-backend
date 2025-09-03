<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$user = authenticateRequest();

// Debug logging
error_log("get_foodsectionby_id.php: User authenticated: " . json_encode($user));

// Check if user is a merchant
if ($user['role'] !== 'merchant') {
    error_log("get_foodsectionby_id.php: User is not a merchant, role: " . $user['role']);
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Only merchants can access food sections.'
    ]);
    exit;
}

// Get store for this merchant user
$storeController = new StoreController();
$store = $storeController->getStoreByUserId($user['user_id']);

if (!$store) {
    error_log("get_foodsectionby_id.php: Store not found for user_id: " . $user['user_id']);
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Store not found for this user.'
    ]);
    exit;
}

$id = $_GET['id'] ?? null;

error_log("get_foodsectionby_id.php: Requested section ID: " . $id);
error_log("get_foodsectionby_id.php: User store_id: " . $store['id']);

if (!$id) {
    error_log("get_foodsectionby_id.php: No section ID provided");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
    exit;
}

// Validate ID is numeric
if (!is_numeric($id)) {
    error_log("get_foodsectionby_id.php: Invalid section ID format: " . $id);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Section ID must be a valid number']);
    exit;
}

$controller = new FoodItemController();
error_log("get_foodsectionby_id.php: Calling controller method with id: " . $id . ", user: " . json_encode($user));
$response = $controller->getFoodSectionById($id, $user, $store['id']);

error_log("get_foodsectionby_id.php: Controller response: " . json_encode($response));

echo json_encode($response);
?>
