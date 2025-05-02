<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';      // Your custom CORS config

use Model\Otp;

header('Content-Type: application/json');
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['phone'], $data['new_password'], $data['otp'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$phone = $data['phone'];
$newPassword = password_hash($data['new_password'], PASSWORD_BCRYPT);
$otp = $data['otp'];

$otpModel = new Otp();
$otpData = $otpModel->verifyOtp($phone, $otp, 'password_reset', true);

if (!$otpData) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "OTP not verified or expired"]);
    exit;
}

$conn = (new \Config\Database())->getConnection();
$stmt = $conn->prepare("UPDATE users SET password = :password WHERE phone = :phone");
$stmt->bindParam(":password", $newPassword);
$stmt->bindParam(":phone", $phone);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Password reset successful"]);
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Failed to reset password"]);
}
