<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

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

// Read the JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Confirmation is now optional - users can delete without confirmation

// Log the deletion attempt for audit purposes
$email = $user['email'] ?? 'N/A';
error_log("Account deletion attempt - User ID: {$user['user_id']}, Email: {$email}, Reason: " . ($data['reason'] ?? 'Not provided'));

$userController = new UserController();
$response = $userController->softDeleteAccount($data, $user);

echo json_encode($response);
