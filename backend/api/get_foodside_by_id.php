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

$data = $_GET; // expecting 'item_id' parameter

if (!isset($data['item_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Item ID is required']);
    exit;
}

$itemId = $data['item_id'];

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->getFoodItemSides($itemId, $user);
echo json_encode($response);
