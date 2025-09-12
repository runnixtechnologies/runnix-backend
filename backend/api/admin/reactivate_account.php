<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/auth.php';

use Controller\UserController;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Only POST requests are accepted."]);
    exit;
}

// Authenticate the request
$authResult = authenticateRequest();
if (!$authResult['authenticated']) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => $authResult['message']]);
    exit;
}

$user = $authResult['user'];

// Check if user is admin
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Admin privileges required."]);
    exit;
}

// Read the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate that user_id is provided
if (empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "user_id is required"]);
    exit;
}

$userId = $data['user_id'];

// Log the reactivation attempt for audit purposes
error_log("Account reactivation attempt by admin - Admin ID: {$user['user_id']}, Target User ID: {$userId}");

$userController = new UserController();
$response = $userController->reactivateAccount($userId, $user);

echo json_encode($response);
