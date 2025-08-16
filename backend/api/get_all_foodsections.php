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

if (!isset($user['store_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Authenticated user does not have store_id']);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

$controller = new FoodItemController();
$response = $controller->getAllFoodSectionsByStoreId($user, $page, $limit);

echo json_encode($response);
?>
