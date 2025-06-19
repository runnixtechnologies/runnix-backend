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

$data = json_decode(file_get_contents("php://input"), true) ?? [];

$user = authenticateRequest();
$controller = new FoodItemController();

if (!isset($data['id']) || !is_numeric($data['id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Valid Food item ID is required']);
    exit;
}


$id = $data['id']; // extract the id from $data

$response = $controller->delete($id, $user);
echo json_encode($response);

