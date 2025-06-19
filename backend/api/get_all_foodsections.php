<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\FoodItemController;

header('Content-Type: application/json');

$storeId = $_GET['store_id'] ?? null;

if (!$storeId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Store ID is required']);
    exit;
}

$controller = new FoodItemController();
$response = $controller->getAllFoodSectionsByStoreId($storeId);

echo json_encode($response);
