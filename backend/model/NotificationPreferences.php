<?php

namespace Model;

use Config\Database;
use PDO;
use PDOException;

class NotificationPreferences
{
    private $conn;
    private $table = "user_notification_preferences";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Get user notification preferences
     */
    public function getUserPreferences($userId, $userType = 'merchant')
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND user_type = :user_type";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no preferences exist, create default ones
            if (!$preferences) {
                return $this->createDefaultPreferences($userId, $userType);
            }
            
            return $preferences;
            
        } catch (PDOException $e) {
            error_log("Error getting notification preferences: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create default notification preferences for a user
     */
    public function createDefaultPreferences($userId, $userType = 'merchant')
    {
        try {
            $sql = "INSERT INTO {$this->table} (
                user_id, user_type,
                push_notifications_enabled, push_order_notifications, push_payment_notifications, 
                push_delivery_notifications, push_promotional_notifications, push_system_notifications, push_support_notifications,
                sms_notifications_enabled, sms_order_notifications, sms_payment_notifications,
                sms_delivery_notifications, sms_promotional_notifications, sms_system_notifications, sms_support_notifications,
                email_notifications_enabled, email_order_notifications, email_payment_notifications,
                email_delivery_notifications, email_promotional_notifications, email_system_notifications, email_support_notifications,
                quiet_hours_start, quiet_hours_end, quiet_hours_enabled, timezone
            ) VALUES (
                :user_id, :user_type,
                1, 1, 1, 1, 0, 1, 1,
                1, 1, 1, 1, 0, 1, 1,
                1, 1, 1, 1, 0, 1, 1,
                '22:00:00', '08:00:00', 0, 'Africa/Lagos'
            )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            
            return $this->getUserPreferences($userId, $userType);
            
        } catch (PDOException $e) {
            error_log("Error creating default notification preferences: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences($userId, $userType, $preferences)
    {
        try {
            // Email notifications are always enabled - force them to TRUE
            $preferences['email_notifications_enabled'] = true;
            $preferences['email_order_notifications'] = true;
            $preferences['email_payment_notifications'] = true;
            $preferences['email_delivery_notifications'] = true;
            $preferences['email_system_notifications'] = true;
            $preferences['email_support_notifications'] = true;
            
            $allowedFields = [
                'push_notifications_enabled', 'push_order_notifications', 'push_payment_notifications',
                'push_delivery_notifications', 'push_promotional_notifications', 'push_system_notifications', 'push_support_notifications',
                'sms_notifications_enabled', 'sms_order_notifications', 'sms_payment_notifications',
                'sms_delivery_notifications', 'sms_promotional_notifications', 'sms_system_notifications', 'sms_support_notifications',
                'email_promotional_notifications', // Only promotional emails can be disabled
                'quiet_hours_start', 'quiet_hours_end', 'quiet_hours_enabled', 'timezone',
                'sms_billing_enabled', 'sms_billing_wallet_enabled', 'sms_billing_paystack_enabled'
            ];
            
            $updateFields = [];
            $values = ['user_id' => $userId, 'user_type' => $userType];
            
            foreach ($allowedFields as $field) {
                if (isset($preferences[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $values[$field] = $preferences[$field];
                }
            }
            
            if (empty($updateFields)) {
                return ['status' => 'error', 'message' => 'No valid preferences to update'];
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . ", updated_at = NOW() 
                    WHERE user_id = :user_id AND user_type = :user_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($values);
            
            return [
                'status' => 'success',
                'message' => 'Notification preferences updated successfully',
                'updated_fields' => array_keys($values)
            ];
            
        } catch (PDOException $e) {
            error_log("Error updating notification preferences: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to update preferences: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user has specific notification type enabled
     */
    public function isNotificationEnabled($userId, $userType, $channel, $notificationType)
    {
        try {
            $sql = "SELECT {$channel}_notifications_enabled, {$channel}_{$notificationType}_notifications 
                    FROM {$this->table} 
                    WHERE user_id = :user_id AND user_type = :user_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // If no preferences exist, create default ones and check again
                $this->createDefaultPreferences($userId, $userType);
                return $this->isNotificationEnabled($userId, $userType, $channel, $notificationType);
            }
            
            // Email notifications are always enabled
            if ($channel === 'email') {
                return true;
            }
            
            return $result["{$channel}_notifications_enabled"] && $result["{$channel}_{$notificationType}_notifications"];
            
        } catch (PDOException $e) {
            error_log("Error checking notification preference: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is in quiet hours
     */
    public function isInQuietHours($userId, $userType)
    {
        try {
            $sql = "SELECT quiet_hours_enabled, quiet_hours_start, quiet_hours_end, timezone 
                    FROM {$this->table} 
                    WHERE user_id = :user_id AND user_type = :user_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['quiet_hours_enabled']) {
                return false;
            }
            
            // Set timezone
            $timezone = $result['timezone'] ?? 'Africa/Lagos';
            date_default_timezone_set($timezone);
            
            $currentTime = date('H:i:s');
            $startTime = $result['quiet_hours_start'];
            $endTime = $result['quiet_hours_end'];
            
            // Handle overnight quiet hours (e.g., 22:00 to 08:00)
            if ($startTime > $endTime) {
                return ($currentTime >= $startTime || $currentTime <= $endTime);
            } else {
                return ($currentTime >= $startTime && $currentTime <= $endTime);
            }
            
        } catch (PDOException $e) {
            error_log("Error checking quiet hours: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification statistics for admin
     */
    public function getNotificationStats()
    {
        try {
            $sql = "SELECT 
                        user_type,
                        COUNT(*) as total_users,
                        SUM(push_notifications_enabled) as push_enabled_count,
                        SUM(sms_notifications_enabled) as sms_enabled_count,
                        SUM(email_notifications_enabled) as email_enabled_count,
                        SUM(quiet_hours_enabled) as quiet_hours_enabled_count
                    FROM {$this->table} 
                    GROUP BY user_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting notification stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Bulk update preferences for multiple users
     */
    public function bulkUpdatePreferences($userIds, $userType, $preferences)
    {
        try {
            // Email notifications are always enabled
            $preferences['email_notifications_enabled'] = true;
            
            $allowedFields = [
                'push_notifications_enabled', 'push_order_notifications', 'push_payment_notifications',
                'push_delivery_notifications', 'push_promotional_notifications', 'push_system_notifications', 'push_support_notifications',
                'sms_notifications_enabled', 'sms_order_notifications', 'sms_payment_notifications',
                'sms_delivery_notifications', 'sms_promotional_notifications', 'sms_system_notifications', 'sms_support_notifications',
                'email_promotional_notifications'
            ];
            
            $updateFields = [];
            $values = ['user_type' => $userType];
            
            foreach ($allowedFields as $field) {
                if (isset($preferences[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                    $values[$field] = $preferences[$field];
                }
            }
            
            if (empty($updateFields)) {
                return ['status' => 'error', 'message' => 'No valid preferences to update'];
            }
            
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . ", updated_at = NOW() 
                    WHERE user_id IN ($placeholders) AND user_type = :user_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_merge($userIds, array_values($values)));
            
            return [
                'status' => 'success',
                'message' => 'Bulk notification preferences updated successfully',
                'updated_count' => $stmt->rowCount()
            ];
            
        } catch (PDOException $e) {
            error_log("Error bulk updating notification preferences: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to bulk update preferences: ' . $e->getMessage()];
        }
    }

    /**
     * Reset user preferences to default
     */
    public function resetToDefault($userId, $userType)
    {
        try {
            // Reset only the channel-level preferences to default
            $sql = "UPDATE {$this->table} SET 
                        push_notifications_enabled = 1,
                        sms_notifications_enabled = 1,
                        email_notifications_enabled = 1,
                        updated_at = NOW()
                    WHERE user_id = :user_id AND user_type = :user_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType
            ]);
            
            return [
                'status' => 'success',
                'message' => 'Notification preferences reset to default successfully',
                'reset_fields' => ['push_notifications_enabled', 'sms_notifications_enabled', 'email_notifications_enabled']
            ];
            
        } catch (PDOException $e) {
            error_log("Error resetting notification preferences: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to reset preferences: ' . $e->getMessage()];
        }
    }
}
