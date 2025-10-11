<?php

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Service\NotificationService;
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

    $notificationService = new NotificationService();
    
    // Validate required fields
    if (!isset($data['user_id']) || !isset($data['user_type'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'user_id and user_type are required']);
        exit;
    }

    $userId = $data['user_id'];
    $userType = $data['user_type'];
    
    // Check if using template or custom notification
    if (isset($data['template_key'])) {
        // Send notification using template
        $variables = $data['variables'] ?? [];
        $referenceId = $data['reference_id'] ?? null;
        $referenceType = $data['reference_type'] ?? null;
        
        $response = $notificationService->sendNotificationByTemplate(
            $userId,
            $userType,
            $data['template_key'],
            $variables,
            $referenceId,
            $referenceType
        );
    } else {
        // Send custom notification
        if (!isset($data['title']) || !isset($data['message'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'title and message are required for custom notifications']);
            exit;
        }
        
        $channels = $data['channels'] ?? ['push', 'sms', 'email'];
        $notificationType = $data['notification_type'] ?? 'system';
        $referenceId = $data['reference_id'] ?? null;
        $referenceType = $data['reference_type'] ?? null;
        
        $response = $notificationService->sendCustomNotification(
            $userId,
            $userType,
            $data['title'],
            $data['message'],
            $channels,
            $notificationType,
            $referenceId,
            $referenceType
        );
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Send notification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
}
