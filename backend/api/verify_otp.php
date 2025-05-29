<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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

$phone = '234' . $data['phone']; // Add this line to match sendOtp format
$otp = $data['otp'];

// Validate phone number (only Nigeria numbers)
if (!preg_match('/^234\d{10}$/', $phone)) {
    http_response_code(401);
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
//$response = $userController->verifyOtp($data['phone'], $data['otp']);
$response = $userController->verifyOtp($phone, $data['otp']); // Now using $phone with 234 prefix
echo json_encode($response);
