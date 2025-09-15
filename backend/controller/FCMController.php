<?php

namespace Controller;

use Service\FCMService;
use Model\FCMToken;
use function Middleware\authenticateRequest;

class FCMController
{
    private $fcmService;
    private $fcmTokenModel;

    public function __construct()
    {
        $this->fcmService = new FCMService();
        $this->fcmTokenModel = new FCMToken();
    }

    /**
     * Register FCM token for user
     */
    public function registerToken($data, $user)
    {
        try {
            // Validate required fields
            if (!isset($data['token']) || empty($data['token'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'FCM token is required'];
            }

            $token = $data['token'];
            $deviceType = $data['device_type'] ?? 'android';
            $deviceId = $data['device_id'] ?? null;
            $appVersion = $data['app_version'] ?? null;

            // Validate device type
            if (!in_array($deviceType, ['android', 'ios', 'web'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Invalid device type'];
            }

            // Save token to database
            $result = $this->fcmTokenModel->saveToken(
                $user['user_id'],
                $token,
                $deviceType,
                $deviceId,
                $appVersion
            );

            if ($result['status'] === 'success') {
                http_response_code(200);
                return [
                    'status' => 'success',
                    'message' => 'FCM token registered successfully',
                    'action' => $result['action']
                ];
            } else {
                http_response_code(500);
                return $result;
            }

        } catch (\Exception $e) {
            error_log("FCM Token Registration Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to register FCM token'];
        }
    }

    /**
     * Send notification to user
     */
    public function sendNotification($data, $user)
    {
        try {
            // Validate required fields
            if (!isset($data['title']) || !isset($data['body'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Title and body are required'];
            }

            $title = $data['title'];
            $body = $data['body'];
            $notificationData = $data['data'] ?? [];
            $targetUserId = $data['target_user_id'] ?? $user['user_id'];

            // Get user's FCM tokens
            $userTokens = $this->fcmTokenModel->getUserTokens($targetUserId);
            
            if (empty($userTokens)) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No FCM tokens found for user'];
            }

            // Extract tokens
            $tokens = array_column($userTokens, 'token');

            // Send notification
            if (count($tokens) === 1) {
                $result = $this->fcmService->sendToDevice($tokens[0], $title, $body, $notificationData);
            } else {
                $result = $this->fcmService->sendToMultipleDevices($tokens, $title, $body, $notificationData);
            }

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Send Notification Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send notification'];
        }
    }

    /**
     * Send notification to multiple users
     */
    public function sendBulkNotification($data, $user)
    {
        try {
            // Validate required fields
            if (!isset($data['title']) || !isset($data['body']) || !isset($data['user_ids'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Title, body, and user_ids are required'];
            }

            $title = $data['title'];
            $body = $data['body'];
            $notificationData = $data['data'] ?? [];
            $userIds = $data['user_ids'];

            if (!is_array($userIds) || empty($userIds)) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'user_ids must be a non-empty array'];
            }

            // Get tokens for all users
            $allTokens = $this->fcmTokenModel->getUsersTokens($userIds);
            
            if (empty($allTokens)) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No FCM tokens found for specified users'];
            }

            // Extract tokens
            $tokens = array_column($allTokens, 'token');

            // Send notification
            $result = $this->fcmService->sendToMultipleDevices($tokens, $title, $body, $notificationData);

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Bulk Notification Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send bulk notification'];
        }
    }

    /**
     * Send notification to topic
     */
    public function sendTopicNotification($data, $user)
    {
        try {
            // Validate required fields
            if (!isset($data['title']) || !isset($data['body']) || !isset($data['topic'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Title, body, and topic are required'];
            }

            $title = $data['title'];
            $body = $data['body'];
            $topic = $data['topic'];
            $notificationData = $data['data'] ?? [];

            // Send topic notification
            $result = $this->fcmService->sendToTopic($topic, $title, $body, $notificationData);

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Topic Notification Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to send topic notification'];
        }
    }

    /**
     * Subscribe user to topic
     */
    public function subscribeToTopic($data, $user)
    {
        try {
            if (!isset($data['topic'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Topic is required'];
            }

            $topic = $data['topic'];
            $userId = $data['target_user_id'] ?? $user['user_id'];

            // Get user's tokens
            $userTokens = $this->fcmTokenModel->getUserTokens($userId);
            
            if (empty($userTokens)) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No FCM tokens found for user'];
            }

            $tokens = array_column($userTokens, 'token');

            // Subscribe to topic
            $result = $this->fcmService->subscribeToTopic($tokens, $topic);

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Subscribe Topic Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to subscribe to topic'];
        }
    }

    /**
     * Unsubscribe user from topic
     */
    public function unsubscribeFromTopic($data, $user)
    {
        try {
            if (!isset($data['topic'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Topic is required'];
            }

            $topic = $data['topic'];
            $userId = $data['target_user_id'] ?? $user['user_id'];

            // Get user's tokens
            $userTokens = $this->fcmTokenModel->getUserTokens($userId);
            
            if (empty($userTokens)) {
                http_response_code(404);
                return ['status' => 'error', 'message' => 'No FCM tokens found for user'];
            }

            $tokens = array_column($userTokens, 'token');

            // Unsubscribe from topic
            $result = $this->fcmService->unsubscribeFromTopic($tokens, $topic);

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Unsubscribe Topic Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to unsubscribe from topic'];
        }
    }

    /**
     * Get user's FCM tokens
     */
    public function getUserTokens($userId, $user)
    {
        try {
            // Check if user can access this data
            if ($user['user_id'] != $userId && $user['role'] !== 'admin') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to access this data'];
            }

            $tokens = $this->fcmTokenModel->getUserTokens($userId);

            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $tokens,
                'count' => count($tokens)
            ];

        } catch (\Exception $e) {
            error_log("FCM Get Tokens Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to get user tokens'];
        }
    }

    /**
     * Deactivate FCM token
     */
    public function deactivateToken($data, $user)
    {
        try {
            if (!isset($data['token'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Token is required'];
            }

            $token = $data['token'];
            $userId = $data['target_user_id'] ?? $user['user_id'];

            // Check if user can deactivate this token
            if ($user['user_id'] != $userId && $user['role'] !== 'admin') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to deactivate this token'];
            }

            $result = $this->fcmTokenModel->deactivateToken($userId, $token);

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Deactivate Token Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to deactivate token'];
        }
    }

    /**
     * Get FCM statistics
     */
    public function getStats($user)
    {
        try {
            // Only admins can view stats
            if ($user['role'] !== 'admin') {
                http_response_code(403);
                return ['status' => 'error', 'message' => 'Unauthorized to view statistics'];
            }

            $stats = $this->fcmTokenModel->getTokenStats();

            http_response_code(200);
            return [
                'status' => 'success',
                'data' => $stats
            ];

        } catch (\Exception $e) {
            error_log("FCM Stats Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to get statistics'];
        }
    }

    /**
     * Validate FCM token
     */
    public function validateToken($data, $user)
    {
        try {
            if (!isset($data['token'])) {
                http_response_code(400);
                return ['status' => 'error', 'message' => 'Token is required'];
            }

            $token = $data['token'];
            $result = $this->fcmService->validateToken($token);

            http_response_code(200);
            return $result;

        } catch (\Exception $e) {
            error_log("FCM Validate Token Error: " . $e->getMessage());
            http_response_code(500);
            return ['status' => 'error', 'message' => 'Failed to validate token'];
        }
    }
}
