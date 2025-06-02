<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\OtpController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$phone = $data['phone'] ?? null;
$email = $data['email'] ?? null;
$purpose = $data['purpose'] ?? 'signup';
$user_id = $data['user_id'] ?? null;

// At least one of phone or email must be provided
if (!$phone && !$email) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Phone or Email is required"]);
    exit;
}

// Validate email if present
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

// Validate phone if present
if ($phone && !preg_match('/^0?\d{10}$/', $phone)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Phone must be 10 or 11 digits"]);
    exit;
}

// Format phone number to international format (Nigeria)
if ($phone) {
    $phone = '234' . ltrim($phone, '0');
}

$controller = new OtpController();
$response = $controller->sendOtp($phone, $purpose, $email, $user_id);
echo json_encode($response);
