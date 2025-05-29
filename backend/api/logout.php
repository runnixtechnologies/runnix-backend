<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../config/JwtHandler.php';

header('Content-Type: application/json');

use Controller\UserController;

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    echo json_encode(["status" => "error", "message" => "Authorization token not found"]);
    exit;
}

if (!preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
    echo json_encode(["status" => "error", "message" => "Invalid Authorization header format"]);
    exit;
}

$token = $matches[1];

// Instantiate the UserController
$controller = new UserController();

// Call the logout method and pass the token
$response = $controller->logout(['token' => $token]);

echo json_encode($response);
