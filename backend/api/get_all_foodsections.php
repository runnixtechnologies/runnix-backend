<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php'; // ✅ Important!

use Controller\FoodItemController;
use Controller\StoreController;
use function Middleware\authenticateRequest; // ✅ Import function

header('Content-Type: application/json');

// ✅ Authenticate user properly
$user = authenticateRequest();

// Debug logging
error_log("get_all_foodsections.php: User authenticated: " . json_encode($user));

// Get store for this merchant user
$storeController = new StoreController();
$store = $storeController->getStoreByUserId($user['user_id']);

if (!$store) {
    error_log("get_all_foodsections.php: Store not found for user_id: " . $user['user_id']);
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Store not found for this user.']);
    exit;
}

error_log("get_all_foodsections.php: Store ID: " . $store['id']);

// Get pagination parameters
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

error_log("get_all_foodsections.php: Page: " . $page . ", Limit: " . $limit);

$controller = new FoodItemController();
$response = $controller->getAllFoodSectionsByStoreId($store['id'], $user, $page, $limit);

error_log("get_all_foodsections.php: Controller response: " . json_encode($response));

echo json_encode($response);
?>
