<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

use Controller\UserController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

$phone = $data['phone'] ?? null;
$email = $data['email'] ?? null;
$otp = $data['otp'] ?? null;

if ((!$phone && !$email) || !$otp) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "OTP and either phone or email are required"]);
    exit;
}

// Validate OTP format (assumes 6 digits)
if (!preg_match('/^\d{6}$/', $otp)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid OTP format"]);
    exit;
}

$userController = new UserController();

if ($phone) {
    // Format Nigerian phone (e.g., 0813... to 234813...)
    if (!preg_match('/^0?\d{10}$/', $phone)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid phone number"]);
        exit;
    }

    $formattedPhone = '234' . ltrim($phone, '0');
    $response = $userController->verifyPhoneOtp($formattedPhone, $otp);

} elseif ($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid email address"]);
        exit;
    }

    $response = $userController->verifyEmailOtp($email, $otp);
}

echo json_encode($response);
