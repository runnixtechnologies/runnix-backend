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

// Check if user is a merchant
if ($user['role'] !== 'merchant') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Only merchants can access food sections']);
    exit;
}

// Extract store_id from authenticated user
if (!isset($user['store_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Store ID not found. Please ensure you are logged in as a merchant with a store setup.']);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;

$controller = new FoodItemController();
$response = $controller->getAllFoodSectionsByStoreId($user['store_id'], $user, $page, $limit);
echo json_encode($response);
?>
