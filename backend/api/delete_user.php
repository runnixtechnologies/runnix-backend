<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

// Read the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate that user_id is provided
if (empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "user_id is required"]);
    exit;
}

$userId = $data['user_id'];

$userController = new UserController();
$response = $userController->deleteUser($userId);

echo json_encode($response);
