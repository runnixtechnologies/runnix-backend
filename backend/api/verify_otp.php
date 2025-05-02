<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

if (empty($data['phone']) || empty($data['otp'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Phone and OTP are required"]);
    exit;
}

$phone = $data['phone'];
$otp = $data['otp'];

// Validate phone number (only Nigeria numbers)
if (!preg_match('/^\d{10}$/', $phone)) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Invalid phone number format"]);
    exit;
}

// Validate OTP format (optional check, assuming OTP is 6 digits)
if (!preg_match('/^\d{6}$/', $otp)) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Invalid OTP format"]);
    exit;
}

$userController = new UserController();
$response = $userController->verifyOtp($data['phone'], $data['otp']);

echo json_encode($response);
