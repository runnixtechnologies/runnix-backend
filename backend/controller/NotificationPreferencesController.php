<?php

namespace Controller;

use Model\NotificationPreferences;
use function Middleware\authenticateRequest;

class NotificationPreferencesController
{
    private $notificationPreferencesModel;

    public function __construct()
    {
        $this->notificationPreferencesModel = new NotificationPreferences();
    }

    /**
     * Get user notification preferences
     */
    public function getUserPreferences($user)
    {
        try {
            $userId = $user['user_id'];
            $userType = $user['role'] ?? 'merchant';
            
            $preferences = $this->notificationPreferencesModel->getUserPreferences($userId, $userType);
            
            if (!$preferences) {
                http_response_code(500);
                return ['status' => 'error', 'message' => 'Failed to retrieve notification preferences'];
            }
            
            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $preferences
            ];
            
        } catch (\Exception $e) {
            error_log("Get notification preferences error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Update user notification preferences
     */
    public function updatePreferences($data, $user)
    {
        try {
            $userId = $user['user_id'];
            $userType = $user['role'] ?? 'merchant';
            
            // Validate required fields
            if (empty($data)) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Preferences data is required'];
            }
            
            // Validate boolean fields
            $booleanFields = [
                'push_notifications_enabled', 'push_order_notifications', 'push_payment_notifications',
                'push_delivery_notifications', 'push_promotional_notifications', 'push_system_notifications', 'push_support_notifications',
                'sms_notifications_enabled', 'sms_order_notifications', 'sms_payment_notifications',
                'sms_delivery_notifications', 'sms_promotional_notifications', 'sms_system_notifications', 'sms_support_notifications',
                'email_promotional_notifications', 'quiet_hours_enabled',
                'sms_billing_enabled', 'sms_billing_wallet_enabled', 'sms_billing_paystack_enabled'
            ];
            
            foreach ($booleanFields as $field) {
                if (isset($data[$field]) && !is_bool($data[$field])) {
                    // Convert string to boolean
                    $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
            
            // Validate time fields
            if (isset($data['quiet_hours_start']) && !$this->isValidTime($data['quiet_hours_start'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Invalid quiet hours start time format'];
            }
            
            if (isset($data['quiet_hours_end']) && !$this->isValidTime($data['quiet_hours_end'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Invalid quiet hours end time format'];
            }
            
            // Validate timezone
            if (isset($data['timezone']) && !$this->isValidTimezone($data['timezone'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Invalid timezone'];
            }
            
            $result = $this->notificationPreferencesModel->updatePreferences($userId, $userType, $data);
            
            if ($result['status'] === 'error') {
                http_response_code(400);
                return $result;
            }
            
            http_response_code(200);
            return $result;
            
        } catch (\Exception $e) {
            error_log("Update notification preferences error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Reset preferences to default
     */
    public function resetToDefault($user)
    {
        try {
            $userId = $user['user_id'];
            $userType = $user['role'] ?? 'merchant';
            
            $result = $this->notificationPreferencesModel->resetToDefault($userId, $userType);
            
            if ($result['status'] === 'error') {
                http_response_code(500);
                return $result;
            }
            
            http_response_code(200);
            return $result;
            
        } catch (\Exception $e) {
            error_log("Reset notification preferences error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Get notification statistics (admin only)
     */
    public function getNotificationStats($user)
    {
        try {
            // Check if user is admin
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to view notification statistics'];
            }
            
            $stats = $this->notificationPreferencesModel->getNotificationStats();
            
            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $stats
            ];
            
        } catch (\Exception $e) {
            error_log("Get notification stats error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Bulk update preferences (admin only)
     */
    public function bulkUpdatePreferences($data, $user)
    {
        try {
            // Check if user is admin
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to perform bulk updates'];
            }
            
            // Validate required fields
            if (!isset($data['user_ids']) || !is_array($data['user_ids']) || empty($data['user_ids'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'user_ids array is required'];
            }
            
            if (!isset($data['user_type']) || !in_array($data['user_type'], ['merchant', 'user', 'rider'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Valid user_type is required'];
            }
            
            $preferences = $data['preferences'] ?? [];
            
            $result = $this->notificationPreferencesModel->bulkUpdatePreferences(
                $data['user_ids'],
                $data['user_type'],
                $preferences
            );
            
            if ($result['status'] === 'error') {
                http_response_code(400);
                return $result;
            }
            
            http_response_code(200);
            return $result;
            
        } catch (\Exception $e) {
            error_log("Bulk update notification preferences error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Check if specific notification is enabled for user
     */
    public function checkNotificationEnabled($data, $user)
    {
        try {
            $userId = $user['user_id'];
            $userType = $user['role'] ?? 'merchant';
            
            // Validate required fields
            if (!isset($data['channel']) || !in_array($data['channel'], ['push', 'sms', 'email'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Valid channel is required'];
            }
            
            if (!isset($data['notification_type']) || !in_array($data['notification_type'], ['order', 'payment', 'delivery', 'system', 'promotion', 'support'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Valid notification_type is required'];
            }
            
            $isEnabled = $this->notificationPreferencesModel->isNotificationEnabled(
                $userId,
                $userType,
                $data['channel'],
                $data['notification_type']
            );
            
            http_response_code(200);
            return [
                'status' => 'success',
                'enabled' => $isEnabled,
                'channel' => $data['channel'],
                'notification_type' => $data['notification_type']
            ];
            
        } catch (\Exception $e) {
            error_log("Check notification enabled error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Check if user is in quiet hours
     */
    public function checkQuietHours($user)
    {
        try {
            $userId = $user['user_id'];
            $userType = $user['role'] ?? 'merchant';
            
            $isInQuietHours = $this->notificationPreferencesModel->isInQuietHours($userId, $userType);
            
            http_response_code(200);
            return [
                'status' => 'success',
                'in_quiet_hours' => $isInQuietHours,
                'current_time' => date('H:i:s'),
                'timezone' => date_default_timezone_get()
            ];
            
        } catch (\Exception $e) {
            error_log("Check quiet hours error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Internal server error'];
        }
    }

    /**
     * Validate time format (HH:MM:SS)
     */
    private function isValidTime($time)
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time);
    }

    /**
     * Validate timezone
     */
    private function isValidTimezone($timezone)
    {
        return in_array($timezone, timezone_identifiers_list());
    }

    /**
     * Get available notification channels
     */
    public function getAvailableChannels()
    {
        http_response_code(200);
        return [
            'status' => 'success',
            'data' => [
                'channels' => [
                    'push' => [
                        'name' => 'Push Notifications',
                        'description' => 'In-app notifications',
                        'can_disable' => true,
                        'types' => ['order', 'payment', 'delivery', 'system', 'promotion', 'support']
                    ],
                    'sms' => [
                        'name' => 'SMS Notifications',
                        'description' => 'Text message notifications',
                        'can_disable' => true,
                        'types' => ['order', 'payment', 'delivery', 'system', 'promotion', 'support'],
                        'note' => 'SMS notifications may incur charges in the future'
                    ],
                    'email' => [
                        'name' => 'Email Notifications',
                        'description' => 'Email notifications',
                        'can_disable' => false,
                        'types' => ['order', 'payment', 'delivery', 'system', 'promotion', 'support'],
                        'note' => 'Email notifications cannot be disabled for security and tracking purposes'
                    ]
                ],
                'notification_types' => [
                    'order' => 'Order-related notifications',
                    'payment' => 'Payment and billing notifications',
                    'delivery' => 'Delivery and logistics notifications',
                    'system' => 'System and maintenance notifications',
                    'promotion' => 'Promotional and marketing notifications',
                    'support' => 'Customer support notifications'
                ]
            ]
        ];
    }
}
