
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

$user = authenticateRequest();
if (!isset($user['store_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Authenticated user does not have store_id']);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate pagination parameters
if ($page < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Page must be greater than 0']);
    exit;
}

if ($limit < 1 || $limit > 100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Limit must be between 1 and 100']);
    exit;
}

$store_id = $user['store_id'];
$controller = new FoodItemController();

$response = $controller->getAllFoodSidesByStoreId($store_id, $user, $page, $limit);
echo json_encode($response);
