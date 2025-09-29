<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';

use Controller\UserController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Check authentication
$user = authenticateRequest();

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

// Get request data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['latitude']) || !isset($data['longitude'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Latitude and longitude are required"]);
    exit;
}

// Validate coordinates
if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid coordinates"]);
    exit;
}

// Call controller
$controller = new UserController();
$response = $controller->saveUserLocation($user, $data);

echo json_encode($response);
