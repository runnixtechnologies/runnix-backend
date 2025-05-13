<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\OtpController;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($data['phone']) || !isset($data['otp'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Phone number and OTP are required"
    ]);
    exit;
}

$phone = '234' . ltrim($data['phone'], '0');
$otp = $data['otp'];

// Call OTP verification method
$otpController = new OtpController();
$response = $otpController->verifyOtp($phone, 'password_reset', $otp);

echo json_encode($response);
