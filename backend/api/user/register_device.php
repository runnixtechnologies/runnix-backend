<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';

use Controller\DeviceController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Authenticate user
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (empty($data['device_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'device_id is required']);
    exit;
}

$deviceController = new DeviceController();

// Extract device data from request
$deviceData = $deviceController->extractDeviceData($data);

// Register device
$result = $deviceController->registerDevice($user, $deviceData);

if ($result) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Device registered successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to register device'
    ]);
}
?>
