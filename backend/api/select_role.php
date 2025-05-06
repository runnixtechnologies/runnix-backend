<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['role'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Role is required"]);
    exit;
}

$allowedRoles = ['user', 'merchant', 'rider'];
if (!in_array($data['role'], $allowedRoles)) {
    http_response_code(400); 
    echo json_encode(["status" => "error", "message" => "Invalid role"]);
    exit;
}

$userController = new UserController();
$response = $userController->selectRole($data['role']);
echo json_encode($response);
