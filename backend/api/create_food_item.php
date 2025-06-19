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

// Handle content type (JSON or FormData)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
} elseif (stripos($contentType, 'multipart/form-data') !== false) {
    $data = $_POST;
} else {
    $data = $_POST;
}

$user = authenticateRequest();
$controller = new FoodItemController();
$response = $controller->create($data,$user);
echo json_encode($response);
