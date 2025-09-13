<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/rateLimitMiddleware.php';

use Controller\OtpController;
use function Middleware\checkOtpRateLimit;
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

// Check rate limit before sending OTP
$rateLimitResult = checkOtpRateLimit($phone, $user['email'], 'password_reset');

// If rate limit check passed, proceed with OTP sending
$otpController = new OtpController();
$response = $otpController->sendOtp($phone, 'password_reset', $user['email'], $user['id']);

// Add rate limit info to response
if ($response['status'] === 'success') {
    $response['rate_limit'] = [
        'allowed' => true,
        'current_count' => $rateLimitResult['details']['phone']['current_count'] ?? 0,
        'max_requests' => $rateLimitResult['details']['phone']['max_requests'] ?? 0,
        'remaining_requests' => $rateLimitResult['details']['phone']['remaining_requests'] ?? 0
    ];
}

echo json_encode($response);
