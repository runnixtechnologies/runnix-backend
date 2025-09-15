<?php

namespace Service;

use Model\NotificationPreferences;
use Model\FCMToken;
use Service\FCMService;
use Config\Database;
use PDO;
use PDOException;

class NotificationService
{
    private $notificationPreferencesModel;
    private $fcmTokenModel;
    private $fcmService;
    private $conn;

    public function __construct()
    {
        $this->notificationPreferencesModel = new NotificationPreferences();
        $this->fcmTokenModel = new FCMToken();
        $this->fcmService = new FCMService();
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Send notification using template
     */
    public function sendNotificationByTemplate($userId, $userType, $templateKey, $variables = [], $referenceId = null, $referenceType = null)
    {
        try {
            // Get template
            $template = $this->getTemplate($templateKey);
            if (!$template) {
                return ['status' => 'error', 'message' => 'Template not found'];
            }

            // Check if template applies to user type
            $userTypes = json_decode($template['user_types'], true);
            if (!in_array($userType, $userTypes)) {
                return ['status' => 'error', 'message' => 'Template not applicable to user type'];
            }

            // Process template variables
            $processedTemplate = $this->processTemplate($template, $variables);

            // Send notifications based on user preferences
            $results = [];
            
            // Check and send push notification
            if ($this->shouldSendNotification($userId, $userType, 'push', $template['template_category'])) {
                $pushResult = $this->sendPushNotification($userId, $userType, $processedTemplate['push_title'], $processedTemplate['push_body'], $variables, $referenceId, $referenceType);
                $results['push'] = $pushResult;
            }

            // Check and send SMS notification
            if ($this->shouldSendNotification($userId, $userType, 'sms', $template['template_category'])) {
                $smsResult = $this->sendSMSNotification($userId, $processedTemplate['sms_message'], $variables, $referenceId, $referenceType);
                $results['sms'] = $smsResult;
            }

            // Always send email notification
            $emailResult = $this->sendEmailNotification($userId, $processedTemplate['email_subject'], $processedTemplate['email_body'], $variables, $referenceId, $referenceType);
            $results['email'] = $emailResult;

            return [
                'status' => 'success',
                'message' => 'Notifications sent successfully',
                'results' => $results,
                'template_key' => $templateKey
            ];

        } catch (\Exception $e) {
            error_log("Send notification by template error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send notifications: ' . $e->getMessage()];
        }
    }

    /**
     * Send custom notification
     */
    public function sendCustomNotification($userId, $userType, $title, $message, $channels = ['push', 'sms', 'email'], $notificationType = 'system', $referenceId = null, $referenceType = null)
    {
        try {
            $results = [];

            foreach ($channels as $channel) {
                if ($this->shouldSendNotification($userId, $userType, $channel, $notificationType)) {
                    switch ($channel) {
                        case 'push':
                            $results['push'] = $this->sendPushNotification($userId, $userType, $title, $message, [], $referenceId, $referenceType);
                            break;
                        case 'sms':
                            $results['sms'] = $this->sendSMSNotification($userId, $message, [], $referenceId, $referenceType);
                            break;
                        case 'email':
                            $results['email'] = $this->sendEmailNotification($userId, $title, $message, [], $referenceId, $referenceType);
                            break;
                    }
                }
            }

            return [
                'status' => 'success',
                'message' => 'Custom notifications sent successfully',
                'results' => $results
            ];

        } catch (\Exception $e) {
            error_log("Send custom notification error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send custom notifications: ' . $e->getMessage()];
        }
    }

    /**
     * Send bulk notifications to multiple users
     */
    public function sendBulkNotification($userIds, $userType, $templateKey, $variables = [], $referenceId = null, $referenceType = null)
    {
        try {
            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($userIds as $userId) {
                $result = $this->sendNotificationByTemplate($userId, $userType, $templateKey, $variables, $referenceId, $referenceType);
                
                if ($result['status'] === 'success') {
                    $successCount++;
                } else {
                    $failureCount++;
                }
                
                $results[] = [
                    'user_id' => $userId,
                    'result' => $result
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Bulk notifications processed',
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results
            ];

        } catch (\Exception $e) {
            error_log("Send bulk notification error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send bulk notifications: ' . $e->getMessage()];
        }
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    private function shouldSendNotification($userId, $userType, $channel, $notificationType)
    {
        // Check if user is in quiet hours
        if ($this->notificationPreferencesModel->isInQuietHours($userId, $userType)) {
            // Only allow urgent notifications during quiet hours
            if ($notificationType !== 'system' && $notificationType !== 'support') {
                return false;
            }
        }

        // Check user preferences
        return $this->notificationPreferencesModel->isNotificationEnabled($userId, $userType, $channel, $notificationType);
    }

    /**
     * Send push notification
     */
    private function sendPushNotification($userId, $userType, $title, $body, $data = [], $referenceId = null, $referenceType = null)
    {
        try {
            // Get user's FCM tokens
            $userTokens = $this->fcmTokenModel->getUserTokens($userId);
            
            if (empty($userTokens)) {
                return ['status' => 'error', 'message' => 'No FCM tokens found for user'];
            }

            $tokens = array_column($userTokens, 'token');
            $data['reference_id'] = $referenceId;
            $data['reference_type'] = $referenceType;

            // Send notification
            if (count($tokens) === 1) {
                $result = $this->fcmService->sendToDevice($tokens[0], $title, $body, $data);
            } else {
                $result = $this->fcmService->sendToMultipleDevices($tokens, $title, $body, $data);
            }

            // Log notification history
            $this->logNotificationHistory($userId, $userType, 'push', $title, $body, $result, $referenceId, $referenceType);

            return $result;

        } catch (\Exception $e) {
            error_log("Send push notification error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send push notification: ' . $e->getMessage()];
        }
    }

    /**
     * Send SMS notification
     */
    private function sendSMSNotification($userId, $message, $data = [], $referenceId = null, $referenceType = null)
    {
        try {
            // Get user's phone number
            $user = $this->getUserPhone($userId);
            if (!$user || empty($user['phone'])) {
                return ['status' => 'error', 'message' => 'No phone number found for user'];
            }

            // Send SMS via Termii (reuse existing OtpController logic)
            $otpController = new \Controller\OtpController();
            $result = $otpController->sendViaTermii($user['phone'], $message);

            // Log notification history
            $this->logNotificationHistory($userId, $user['role'] ?? 'merchant', 'sms', 'SMS Notification', $message, $result, $referenceId, $referenceType);

            return $result;

        } catch (\Exception $e) {
            error_log("Send SMS notification error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send SMS notification: ' . $e->getMessage()];
        }
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification($userId, $subject, $body, $data = [], $referenceId = null, $referenceType = null)
    {
        try {
            // Get user's email
            $user = $this->getUserEmail($userId);
            if (!$user || empty($user['email'])) {
                return ['status' => 'error', 'message' => 'No email found for user'];
            }

            // Send email (reuse existing email functionality)
            $result = $this->sendEmail($user['email'], $subject, $body);

            // Log notification history
            $this->logNotificationHistory($userId, $user['role'] ?? 'merchant', 'email', $subject, $body, $result, $referenceId, $referenceType);

            return $result;

        } catch (\Exception $e) {
            error_log("Send email notification error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send email notification: ' . $e->getMessage()];
        }
    }

    /**
     * Get notification template
     */
    private function getTemplate($templateKey)
    {
        try {
            $sql = "SELECT * FROM notification_templates WHERE template_key = :template_key AND is_active = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['template_key' => $templateKey]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get template error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process template with variables
     */
    private function processTemplate($template, $variables)
    {
        $processedTemplate = $template;
        
        // Replace variables in all template fields
        $fields = ['push_title', 'push_body', 'sms_message', 'email_subject', 'email_body'];
        
        foreach ($fields as $field) {
            $processedTemplate[$field] = $this->replaceVariables($template[$field], $variables);
        }
        
        return $processedTemplate;
    }

    /**
     * Replace variables in text
     */
    private function replaceVariables($text, $variables)
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }
        return $text;
    }

    /**
     * Get user phone number
     */
    private function getUserPhone($userId)
    {
        try {
            $sql = "SELECT phone, role FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user phone error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user email
     */
    private function getUserEmail($userId)
    {
        try {
            $sql = "SELECT email, role FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get user email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email
     */
    private function sendEmail($email, $subject, $body)
    {
        // This is a placeholder - implement your email sending logic
        // You can use PHPMailer, SwiftMailer, or your existing email service
        
        try {
            // For now, return success - implement actual email sending
            return ['status' => 'success', 'message' => 'Email sent successfully'];
            
        } catch (\Exception $e) {
            error_log("Send email error: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Failed to send email: ' . $e->getMessage()];
        }
    }

    /**
     * Log notification history
     */
    private function logNotificationHistory($userId, $userType, $notificationType, $title, $message, $result, $referenceId = null, $referenceType = null)
    {
        try {
            $sql = "INSERT INTO notification_history (
                user_id, user_type, template_key, title, message, notification_type,
                status, external_id, external_provider, reference_id, reference_type
            ) VALUES (
                :user_id, :user_type, :template_key, :title, :message, :notification_type,
                :status, :external_id, :external_provider, :reference_id, :reference_type
            )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'user_type' => $userType,
                'template_key' => 'custom', // or actual template key
                'title' => $title,
                'message' => $message,
                'notification_type' => $notificationType,
                'status' => $result['status'] === 'success' ? 'sent' : 'failed',
                'external_id' => $result['message_id'] ?? null,
                'external_provider' => $notificationType === 'push' ? 'fcm' : ($notificationType === 'sms' ? 'termii' : 'smtp'),
                'reference_id' => $referenceId,
                'reference_type' => $referenceType
            ]);
            
        } catch (PDOException $e) {
            error_log("Log notification history error: " . $e->getMessage());
        }
    }

    /**
     * Get notification history for user
     */
    public function getNotificationHistory($userId, $userType, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM notification_history 
                    WHERE user_id = :user_id AND user_type = :user_type 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':user_type', $userType, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get notification history error: " . $e->getMessage());
            return [];
        }
    }
}
