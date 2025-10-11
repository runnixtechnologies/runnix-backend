<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/rateLimitMiddleware.php';

use Controller\OtpController;
use function Middleware\checkOtpRateLimit;

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

// Check rate limit before sending OTP
$rateLimitResult = checkOtpRateLimit($phone, $email, $purpose);

// If rate limit check passed, proceed with OTP sending
$controller = new OtpController();
$response = $controller->sendOtp($phone, $purpose, $email, $user_id);

// Add rate limit info to response
if ($response['status'] === 'success') {
    $response['rate_limit'] = [
        'allowed' => true,
        'current_count' => $rateLimitResult['details']['phone']['current_count'] ?? $rateLimitResult['details']['email']['current_count'] ?? 0,
        'max_requests' => $rateLimitResult['details']['phone']['max_requests'] ?? $rateLimitResult['details']['email']['max_requests'] ?? 0,
        'remaining_requests' => $rateLimitResult['details']['phone']['remaining_requests'] ?? $rateLimitResult['details']['email']['remaining_requests'] ?? 0
    ];
}

echo json_encode($response);
