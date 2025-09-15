<?php

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\MerchantNotificationController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authentication failed']);
    exit;
}

$controller = new MerchantNotificationController();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
            exit;
        }

        // Determine notification type based on endpoint or data
        $notificationType = $_GET['type'] ?? $data['notification_type'] ?? 'custom';
        
        switch ($notificationType) {
            case 'new_order':
                $response = $controller->sendNewOrderNotification($data, $user);
                break;
            case 'payment_received':
                $response = $controller->sendPaymentReceivedNotification($data, $user);
                break;
            case 'rider_assigned':
                $response = $controller->sendRiderAssignedNotification($data, $user);
                break;
            case 'order_delivered':
                $response = $controller->sendOrderDeliveredNotification($data, $user);
                break;
            case 'account_verification':
                $response = $controller->sendAccountVerificationNotification($data, $user);
                break;
            case 'order_status_update':
                $response = $controller->sendOrderStatusUpdateNotification($data, $user);
                break;
            case 'payment_failed':
            case 'payment':
                $response = $controller->sendPaymentNotification($data, $user);
                break;
            case 'customer_review':
                $response = $controller->sendCustomerReviewNotification($data, $user);
                break;
            case 'customer_message':
                $response = $controller->sendCustomerMessageNotification($data, $user);
                break;
            case 'bulk':
                $response = $controller->sendBulkNotification($data, $user);
                break;
            case 'promotional':
                $response = $controller->sendPromotionalNotification($data, $user);
                break;
            case 'custom':
            default:
                $response = $controller->sendCustomNotification($data, $user);
                break;
        }
        
        echo json_encode($response);
        break;
        
    case 'GET':
        // Get notification history
        $data = $_GET;
        $response = $controller->getNotificationHistory($data, $user);
        echo json_encode($response);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
        break;
}
