<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force error logging to a specific file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\FoodItemController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Log that the endpoint was hit
error_log("=== GET ALL FOOD ITEMS ENDPOINT HIT ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("HTTP Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Query params: " . json_encode($_GET));

$user = authenticateRequest();

// Check if user is a merchant
if ($user['role'] !== 'merchant') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only merchants can access food items']);
    exit;
}

// Extract store_id from authenticated user
if (!isset($user['store_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.']);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

error_log("=== CALLING FOOD ITEM CONTROLLER ===");
error_log("Store ID: " . $user['store_id']);
error_log("Page: " . $page);
error_log("Limit: " . $limit);
error_log("User data: " . json_encode($user));

$controller = new FoodItemController();
$response = $controller->getAllFoodItemsByStoreId($user['store_id'], $user, $page, $limit);

error_log("=== CONTROLLER RESPONSE ===");
error_log("Response: " . json_encode($response));

echo json_encode($response);
