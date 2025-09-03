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

// Get store_id - try from JWT first, then from database
$storeId = $user['store_id'] ?? null;

if (!$storeId) {
    error_log("get_all_foodsections.php: No store_id in JWT, getting from database");
    $storeController = new StoreController();
    $storeResponse = $storeController->getStoreByUserId($user['user_id']);
    
    if ($storeResponse['status'] !== 'success') {
        error_log("get_all_foodsections.php: Store not found for user_id: " . $user['user_id']);
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Store not found for this user.']);
        exit;
    }
    
    $storeId = $storeResponse['store']['id'];
}

error_log("get_all_foodsections.php: Store ID: " . $storeId);

// Get pagination parameters
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

error_log("get_all_foodsections.php: Page: " . $page . ", Limit: " . $limit);

$controller = new FoodItemController();
$response = $controller->getAllFoodSectionsByStoreId($storeId, $user, $page, $limit);

error_log("get_all_foodsections.php: Controller response: " . json_encode($response));

echo json_encode($response);
?>
