<?php

namespace Service;

use Controller\MerchantNotificationController;

class AutoNotificationService
{
    private $merchantNotificationController;

    public function __construct()
    {
        $this->merchantNotificationController = new MerchantNotificationController();
    }

    /**
     * Automatically send notification when order status changes
     */
    public function notifyOrderStatusChange($orderId, $newStatus, $merchantId, $orderNumber, $customerName)
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'status' => $newStatus,
                'customer_name' => $customerName,
                'status_change_time' => date('Y-m-d H:i:s')
            ];

            // Use system user for automatic notifications
            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendOrderStatusUpdateNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (order status): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }

    /**
     * Automatically send notification when payment is processed
     */
    public function notifyPaymentProcessed($paymentId, $merchantId, $amount, $method, $transactionId, $orderNumber, $status = 'received')
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'payment_id' => $paymentId,
                'payment_amount' => $amount,
                'payment_method' => $method,
                'transaction_id' => $transactionId,
                'order_number' => $orderNumber,
                'payment_status' => $status,
                'payment_time' => date('Y-m-d H:i:s')
            ];

            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendPaymentNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (payment): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }

    /**
     * Automatically send notification when account is verified
     */
    public function notifyAccountVerification($merchantId, $status, $adminNotes = null)
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'verification_status' => $status,
                'verification_date' => date('Y-m-d H:i:s'),
                'admin_notes' => $adminNotes,
                'verification_id' => uniqid('verify_')
            ];

            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendAccountVerificationNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (account verification): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }

    /**
     * Automatically send notification when customer leaves review
     */
    public function notifyCustomerReview($reviewId, $merchantId, $customerName, $rating, $reviewText, $orderNumber = null)
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'review_id' => $reviewId,
                'customer_name' => $customerName,
                'rating' => $rating,
                'review_text' => $reviewText,
                'order_number' => $orderNumber,
                'review_date' => date('Y-m-d H:i:s')
            ];

            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendCustomerReviewNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (customer review): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }

    /**
     * Automatically send notification when customer sends message
     */
    public function notifyCustomerMessage($messageId, $merchantId, $customerName, $messageText, $orderId = null)
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'message_id' => $messageId,
                'customer_name' => $customerName,
                'message_text' => $messageText,
                'order_id' => $orderId,
                'message_time' => date('Y-m-d H:i:s')
            ];

            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendCustomerMessageNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (customer message): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }

    /**
     * Automatically send notification for new order
     */
    public function notifyNewOrder($orderId, $merchantId, $orderNumber, $customerName, $customerPhone, $orderTotal, $deliveryAddress, $itemsCount)
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'order_total' => $orderTotal,
                'order_time' => date('Y-m-d H:i:s'),
                'delivery_address' => $deliveryAddress,
                'items_count' => $itemsCount,
                'estimated_prep_time' => '25 minutes'
            ];

            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendNewOrderNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (new order): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }

    /**
     * Automatically send notification for payout processed
     */
    public function notifyPayoutProcessed($payoutId, $merchantId, $amount, $method, $bankAccount, $transactionRef, $period)
    {
        try {
            $data = [
                'merchant_id' => $merchantId,
                'payout_id' => $payoutId,
                'payout_amount' => $amount,
                'payout_method' => $method,
                'bank_account' => $bankAccount,
                'transaction_reference' => $transactionRef,
                'period' => $period,
                'payout_date' => date('Y-m-d H:i:s')
            ];

            $systemUser = ['user_id' => 0, 'role' => 'system'];
            
            return $this->merchantNotificationController->sendCustomNotification($data, $systemUser);
            
        } catch (\Exception $e) {
            error_log("Auto notification error (payout): " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send automatic notification'];
        }
    }
}
