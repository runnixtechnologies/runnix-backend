<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

// Define required fields
$requiredFields = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'phone', 'role'];
foreach ($requiredFields as $field) {
    if (empty($data[$field]) || !isset($data[$field])) {
        http_response_code(400);  // Bad Request
        echo json_encode(["status" => "error", "message" => "$field is required"]);
        exit;
    }
}

// Validate email format
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

// Check if passwords match
if ($data['password'] !== $data['confirm_password']) {
    http_response_code(400);  // Bad Request
    echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
    exit;
}

// Instantiate UserController
$userController = new UserController();

// Call method to create user and handle the personal setup
$response = $userController->setupUserRider($data);

// Return the response
echo json_encode($response);
