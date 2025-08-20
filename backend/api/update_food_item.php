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

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
} elseif (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;  // File will be in $_FILES
} else {
    $data = $_POST;
}


$user = authenticateRequest();

// Validate ID
if (!isset($data['id']) || !is_numeric($data['id']) || $data['id'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid food item ID is required']);
    exit;
}

$controller = new FoodItemController();
$response = $controller->update($data,$user);
echo json_encode($response);
