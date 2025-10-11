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

// Enforce PUT method
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'PUT') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use PUT method.'
    ]);
    exit;
}

// Authenticate the request
try {
    $user = authenticateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit;
}

// Read JSON body for PUT
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? [];

$controller = new UserController();
$response = $controller->updateProfile($data, $user);

echo json_encode($response);
?>


