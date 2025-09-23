<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\UserController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Only POST requests are accepted."]);
    exit;
}

// Authenticate the request
try {
    $user = authenticateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication failed"]);
    exit;
}

// Read input (supports JSON and form-data)
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
if ($data === null || !is_array($data)) {
    // Fallback to form-data
    $data = $_POST ?? [];
}

// If still empty, return helpful message
if (!is_array($data) || empty($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid or empty request body. Ensure Content-Type: application/json and valid JSON payload."]);
    exit;
}

// Validate required fields
if (!isset($data['otp']) || $data['otp'] === '' || $data['otp'] === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "OTP is required"]);
    exit;
}

if (empty($data['verification_type'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Verification type is required"]);
    exit;
}

if (!in_array($data['verification_type'], ['phone', 'email'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid verification type. Must be 'phone' or 'email'"]);
    exit;
}

// Validate profile data is provided
if (empty($data['profile_data'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Profile data is required"]);
    exit;
}

$userController = new UserController();
$response = $userController->verifyProfileUpdate($data, $user);

echo json_encode($response);
?>
