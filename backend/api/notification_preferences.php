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

$controller = new NotificationPreferencesController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Get user notification preferences
        $response = $controller->getUserPreferences($user);
        echo json_encode($response);
        break;
        
    case 'PUT':
    case 'PATCH':
        // Update notification preferences
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
            exit;
        }
        
        $response = $controller->updatePreferences($data, $user);
        echo json_encode($response);
        break;
        
    case 'DELETE':
        // Reset preferences to default
        $response = $controller->resetToDefault($user);
        echo json_encode($response);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
