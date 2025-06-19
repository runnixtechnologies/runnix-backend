<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\FoodItemController;


header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Section ID is required']);
    exit;
}

$controller = new FoodItemController();
$response = $controller->getFoodSectionById($id);

echo json_encode($response);
