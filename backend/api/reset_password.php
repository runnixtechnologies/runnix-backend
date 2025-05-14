<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['phone'], $data['new_password'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Phone and New Password are required"]);
    exit;
}

// Format phone: remove leading 0, prepend 234
$phone = '234' . ltrim($data['phone'], '0');
$newPassword = $data['new_password'];

// Call controller
$controller = new UserController();
$response = $controller->resetPassword($phone, $newPassword);

echo json_encode($response);
