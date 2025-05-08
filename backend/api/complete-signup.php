<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

/*if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}*/

$userController = new UserController();
$response = $userController->finalizeSignup($data);
echo json_encode($response);
