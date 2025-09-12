<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';

use Controller\UserController;
use Middleware;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Only POST requests are accepted."]);
    exit;
}

// Authenticate the request
try {
    $user = Middleware\authenticateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication failed"]);
    exit;
}

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
