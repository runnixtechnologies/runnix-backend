<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$userController = new UserController();
$response = $userController->finalizeSignup($data);

echo json_encode($response);
