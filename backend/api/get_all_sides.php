
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

$data = $_GET; // expecting 'food_item_id' parameter

$user = authenticateRequest();
if (!isset($user['store_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Authenticated user does not have store_id']);
    exit;
}

$store_id = $user['store_id'];
$controller = new FoodItemController();

$response = $controller->getAllFoodSidesByStoreId($store_id, $user);
echo json_encode($response);
