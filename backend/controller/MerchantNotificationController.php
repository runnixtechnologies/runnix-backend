<?php

namespace Controller;

use Service\NotificationService;
use function Middleware\authenticateRequest;

class MerchantNotificationController
{
    private $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Send new order notification to merchant
     */
    public function sendNewOrderNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['order_number', 'customer_name', 'order_total', 'order_time'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'] ?? $user['user_id'];
            
            $variables = [
                'order_number' => $data['order_number'],
                'customer_name' => $data['customer_name'],
                'order_total' => $data['order_total'],
                'order_time' => $data['order_time']
            ];

            $result = $this->notificationService->sendNotificationByTemplate(
                $merchantId,
                'merchant',
                'new_order',
                $variables,
                $data['order_id'] ?? null,
                'order'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send new order notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send new order notification'];
        }
    }

    /**
     * Send payment received notification to merchant
     */
    public function sendPaymentReceivedNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['amount', 'order_number', 'payment_method', 'transaction_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'] ?? $user['user_id'];
            
            $variables = [
                'amount' => $data['amount'],
                'order_number' => $data['order_number'],
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id']
            ];

            $result = $this->notificationService->sendNotificationByTemplate(
                $merchantId,
                'merchant',
                'payment_received',
                $variables,
                $data['payment_id'] ?? null,
                'payment'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send payment received notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send payment received notification'];
        }
    }

    /**
     * Send rider assigned notification to merchant
     */
    public function sendRiderAssignedNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['rider_name', 'rider_phone', 'order_number', 'estimated_delivery'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'] ?? $user['user_id'];
            
            $variables = [
                'rider_name' => $data['rider_name'],
                'rider_phone' => $data['rider_phone'],
                'order_number' => $data['order_number'],
                'estimated_delivery' => $data['estimated_delivery']
            ];

            $result = $this->notificationService->sendNotificationByTemplate(
                $merchantId,
                'merchant',
                'rider_assigned',
                $variables,
                $data['order_id'] ?? null,
                'order'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send rider assigned notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send rider assigned notification'];
        }
    }

    /**
     * Send order delivered notification to merchant
     */
    public function sendOrderDeliveredNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['order_number', 'customer_name', 'delivery_time'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'] ?? $user['user_id'];
            
            $variables = [
                'order_number' => $data['order_number'],
                'customer_name' => $data['customer_name'],
                'delivery_time' => $data['delivery_time']
            ];

            $result = $this->notificationService->sendNotificationByTemplate(
                $merchantId,
                'merchant',
                'order_delivered',
                $variables,
                $data['order_id'] ?? null,
                'order'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send order delivered notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send order delivered notification'];
        }
    }

    /**
     * Send custom notification to merchant
     */
    public function sendCustomNotification($data, $user)
    {
        try {
            // Validate required fields
            if (!isset($data['title']) || !isset($data['message'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Title and message are required'];
            }

            $merchantId = $data['merchant_id'] ?? $user['user_id'];
            $channels = $data['channels'] ?? ['push', 'sms', 'email'];
            $notificationType = $data['notification_type'] ?? 'system';

            $result = $this->notificationService->sendCustomNotification(
                $merchantId,
                'merchant',
                $data['title'],
                $data['message'],
                $channels,
                $notificationType,
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send custom notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send custom notification'];
        }
    }

    /**
     * Send bulk notification to multiple merchants
     */
    public function sendBulkNotification($data, $user)
    {
        try {
            // Check if user is admin or has permission
            if ($user['role'] !== 'admin' && $user['role'] !== 'merchant') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to send bulk notifications'];
            }

            // Validate required fields
            if (!isset($data['merchant_ids']) || !is_array($data['merchant_ids']) || empty($data['merchant_ids'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'merchant_ids array is required'];
            }

            if (!isset($data['template_key'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'template_key is required'];
            }

            $variables = $data['variables'] ?? [];

            $result = $this->notificationService->sendBulkNotification(
                $data['merchant_ids'],
                'merchant',
                $data['template_key'],
                $variables,
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send bulk notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send bulk notification'];
        }
    }

    /**
     * Get merchant notification history
     */
    public function getNotificationHistory($data, $user)
    {
        try {
            $merchantId = $data['merchant_id'] ?? $user['user_id'];
            
            // Check if user can access this merchant's history
            if ($user['role'] !== 'admin' && $user['user_id'] != $merchantId) {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to access this merchant\'s notification history'];
            }

            $limit = $data['limit'] ?? 50;
            $offset = $data['offset'] ?? 0;

            $history = $this->notificationService->getNotificationHistory($merchantId, 'merchant', $limit, $offset);

            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $history,
                'count' => count($history),
                'limit' => $limit,
                'offset' => $offset
            ];

        } catch (\Exception $e) {
            error_log("Get notification history error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to get notification history'];
        }
    }

    /**
     * Send promotional notification to merchants
     */
    public function sendPromotionalNotification($data, $user)
    {
        try {
            // Check if user is admin
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Only admins can send promotional notifications'];
            }

            // Validate required fields
            if (!isset($data['title']) || !isset($data['message'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Title and message are required'];
            }

            $merchantIds = $data['merchant_ids'] ?? [];
            
            // If no specific merchants, get all merchants
            if (empty($merchantIds)) {
                $merchantIds = $this->getAllMerchantIds();
            }

            $result = $this->notificationService->sendBulkNotification(
                $merchantIds,
                'merchant',
                'system_maintenance', // Use system template for promotional
                [
                    'title' => $data['title'],
                    'message' => $data['message']
                ],
                $data['reference_id'] ?? null,
                'promotion'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send promotional notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send promotional notification'];
        }
    }

    /**
     * Send account verification notification
     */
    public function sendAccountVerificationNotification($data, $user)
    {
        try {
            // Validate required fields
            if (!isset($data['merchant_id']) || !isset($data['verification_status'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'merchant_id and verification_status are required'];
            }

            $merchantId = $data['merchant_id'];
            $status = $data['verification_status'];
            
            $title = $status === 'approved' ? 'Account Verified!' : 'Account Verification Update';
            $message = $status === 'approved' 
                ? 'Congratulations! Your merchant account has been verified and approved.'
                : 'Your account verification requires attention. Please check your email for details.';

            $result = $this->notificationService->sendCustomNotification(
                $merchantId,
                'merchant',
                $title,
                $message,
                ['push', 'sms', 'email'],
                'system',
                $data['verification_id'] ?? null,
                'account_verification'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send account verification notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send account verification notification'];
        }
    }

    /**
     * Send order status update notification
     */
    public function sendOrderStatusUpdateNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['merchant_id', 'order_id', 'order_number', 'status', 'customer_name'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'];
            $status = $data['status'];
            
            $statusMessages = [
                'confirmed' => 'Order confirmed and being prepared',
                'preparing' => 'Order is being prepared',
                'ready_for_pickup' => 'Order is ready for pickup',
                'cancelled' => 'Order has been cancelled',
                'completed' => 'Order has been completed'
            ];
            
            $title = "Order #{$data['order_number']} - " . ucfirst(str_replace('_', ' ', $status));
            $message = $statusMessages[$status] ?? "Order status updated to {$status}";

            $result = $this->notificationService->sendCustomNotification(
                $merchantId,
                'merchant',
                $title,
                $message,
                ['push', 'sms', 'email'],
                'order',
                $data['order_id'],
                'order'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send order status update notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send order status update notification'];
        }
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['merchant_id', 'payment_amount', 'payment_method', 'transaction_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'];
            $paymentStatus = $data['payment_status'] ?? 'received';
            
            $title = $paymentStatus === 'failed' ? 'Payment Failed' : 'Payment Received';
            $message = $paymentStatus === 'failed' 
                ? "Payment of ₦{$data['payment_amount']} failed for order #{$data['order_number']}"
                : "Payment of ₦{$data['payment_amount']} received via {$data['payment_method']}";

            $result = $this->notificationService->sendCustomNotification(
                $merchantId,
                'merchant',
                $title,
                $message,
                ['push', 'sms', 'email'],
                'payment',
                $data['payment_id'] ?? null,
                'payment'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send payment notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send payment notification'];
        }
    }

    /**
     * Send customer review notification
     */
    public function sendCustomerReviewNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['merchant_id', 'customer_name', 'rating', 'review_text'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'];
            $rating = $data['rating'];
            
            $title = "New Review from {$data['customer_name']}";
            $message = "⭐ {$rating}/5 stars - {$data['review_text']}";

            $result = $this->notificationService->sendCustomNotification(
                $merchantId,
                'merchant',
                $title,
                $message,
                ['push', 'email'], // Don't send SMS for reviews
                'support',
                $data['review_id'] ?? null,
                'review'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send customer review notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send customer review notification'];
        }
    }

    /**
     * Send customer message notification
     */
    public function sendCustomerMessageNotification($data, $user)
    {
        try {
            // Validate required fields
            $requiredFields = ['merchant_id', 'customer_name', 'message_text'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    http_response_code(400);
                    return ['status' => 'error', 'message' => "$field is required"];
                }
            }

            $merchantId = $data['merchant_id'];
            
            $title = "New Message from {$data['customer_name']}";
            $message = substr($data['message_text'], 0, 100) . (strlen($data['message_text']) > 100 ? '...' : '');

            $result = $this->notificationService->sendCustomNotification(
                $merchantId,
                'merchant',
                $title,
                $message,
                ['push', 'email'], // Don't send SMS for messages
                'support',
                $data['message_id'] ?? null,
                'message'
            );

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("Send customer message notification error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send customer message notification'];
        }
    }

    /**
     * Get all merchant IDs
     */
    private function getAllMerchantIds()
    {
        try {
            $conn = (new \Config\Database())->getConnection();
            $sql = "SELECT id FROM users WHERE role = 'merchant' AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
            
        } catch (\Exception $e) {
            error_log("Get all merchant IDs error: " . $e->getMessage());
            return [];
        }
    }
}
