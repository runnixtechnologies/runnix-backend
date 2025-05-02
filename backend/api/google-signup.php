<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';      // Your custom CORS config
use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

if (empty($data['email']) || empty($data['first_name']) || empty($data['last_name'])) {
    echo json_encode(["status" => "error", "message" => "Missing required Google fields"]);
    exit;
}

$userController = new UserController();
$response = $userController->googlePrefill($data);

echo json_encode($response);
