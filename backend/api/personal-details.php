<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$requiredFields = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "$field is required"]);
        exit;
    }
}

if ($data['password'] !== $data['confirm_password']) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
    exit;
}

$userController = new UserController();
$response = $userController->collectPersonalDetails($data);
echo json_encode($response);
