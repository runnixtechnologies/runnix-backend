<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['business_type'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Business type is required for merchants"]);
    exit;
}

$userController = new UserController();
$response = $userController->collectBusinessType($data);
echo json_encode($response);
