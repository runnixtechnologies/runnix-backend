<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

require_once '../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\DeviceController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Authenticate user (merchant)
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $deviceController = new DeviceController();
    $response = $deviceController->getUserDevices($user);
    echo json_encode($response);
} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Merchant get devices error: " . $e->getMessage(), 3, __DIR__ . '/php-error.log');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error'
    ]);
}
?>
