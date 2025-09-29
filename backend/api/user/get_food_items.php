<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\FoodItemController;
use Middleware\AuthMiddleware;

header('Content-Type: application/json');

// Check authentication
$auth = new AuthMiddleware();
$user = $auth->authenticate();

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Get query parameters
$storeId = $_GET['store_id'] ?? null;
$categoryId = $_GET['category_id'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? 'popular';

// Validate required parameters
if (!$storeId) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Store ID is required"]);
    exit;
}

// Validate sort parameter
$allowedSorts = ['popular', 'newest', 'price_low', 'price_high'];
if (!in_array($sort, $allowedSorts)) {
    $sort = 'popular';
}

// Call controller
$controller = new FoodItemController();
$response = $controller->getFoodItemsForCustomer($storeId, $categoryId, $search, $sort);

echo json_encode($response);
