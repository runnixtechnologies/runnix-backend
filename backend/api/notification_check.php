<?php

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\NotificationPreferencesController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
        exit;
    }

    $controller = new NotificationPreferencesController();
    
    // Check if specific notification is enabled
    if (isset($data['channel']) && isset($data['notification_type'])) {
        $response = $controller->checkNotificationEnabled($data, $user);
    } 
    // Check quiet hours
    else if (isset($data['check_quiet_hours'])) {
        $response = $controller->checkQuietHours($user);
    } 
    else {
        http_response_code(400);
        $response = ['status' => 'error', 'message' => 'Either channel+notification_type or check_quiet_hours is required'];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Notification check error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
