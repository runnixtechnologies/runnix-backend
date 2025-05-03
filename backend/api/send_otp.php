<?php
require_once '../../vendor/autoload.php';
require_once '../config/cors.php'; // Your custom CORS config

use Controller\OtpController;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['phone'])) {
    echo json_encode(["status" => "error", "message" => "Phone number is required"]);
    exit;
}

$phone = $data['phone'];

// Allow 10-11 digits (with optional leading 0)
if (!preg_match('/^0?\d{10}$/', $phone)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Phone must be 10 or 11 digits"]);
    exit;
}


$purpose = $data['purpose'] ?? 'signup';
$email = $data['email'] ?? null;
$user_id = $data['user_id'] ?? null;

// Validate email format if provided
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(401); // Unauthorized
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

// Check if user exists (if user_id is provided)
/*if ($user_id) {
    // Use your model here to check if the user_id exists in the database
    // Assuming a method `getUserById($user_id)` in your user model
    $userModel = new User();
    $user = $userModel->getUserById($user_id);
    if (!$user) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
}*/

$controller = new OtpController();
// Format: Remove leading 0, add 234
$phone = '234' . ltrim($phone, '0');
$response = $controller->sendOtp($phone, $purpose, $email, $user_id);
echo json_encode($response);
