<?php

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';      // Your custom CORS config


use Controller\OtpController;
use PDO;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['phone'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Phone number is required"]);
    exit;
}

$phone = $data['phone'];
$phone = '234' . ltrim($phone, '0');
// Check if phone exists in users table
$conn = (new \Config\Database())->getConnection();
$stmt = $conn->prepare("SELECT id, email FROM users WHERE phone = :phone");
$stmt->bindParam(":phone", $phone);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Phone number not found"]);
    exit;
}

// Send OTP for password reset
$otpController = new OtpController();
// Format: Remove leading 0, add 234

$response = $otpController->sendOtp($phone, 'password_reset', $user['email'], $user['id']);
echo json_encode($response);
