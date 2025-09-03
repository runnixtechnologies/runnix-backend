<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php'; // ✅ Important!

use Controller\FoodItemController;
use function Middleware\authenticateRequest; // ✅ Import function

header('Content-Type: application/json');

// ✅ Authenticate user properly
$user = authenticateRequest();

// Debug logging
error_log("get_all_foodsections.php: User authenticated: " . json_encode($user));

if (!isset($user['store_id'])) {
    error_log("get_all_foodsections.php: No store_id found in user data");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Authenticated user does not have store_id']);
    exit;
}

error_log("get_all_foodsections.php: Store ID: " . $user['store_id']);

// Get pagination parameters
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

error_log("get_all_foodsections.php: Page: " . $page . ", Limit: " . $limit);

$controller = new FoodItemController();
$response = $controller->getAllFoodSectionsByStoreId($user, $page, $limit);

error_log("get_all_foodsections.php: Controller response: " . json_encode($response));

echo json_encode($response);
?>
