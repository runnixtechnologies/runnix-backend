<?php

namespace Model;

use Config\Database;
use PDO;
use PDOException;

class FCMToken
{
    private $conn;
    private $table = "fcm_tokens";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL,
                device_type ENUM('android', 'ios', 'web') DEFAULT 'android',
                device_id VARCHAR(255),
                app_version VARCHAR(50),
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_token (user_id, token),
                INDEX idx_user_id (user_id),
                INDEX idx_token (token),
                INDEX idx_active (is_active)
            )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
        } catch (PDOException $e) {
            error_log("Error creating FCM tokens table: " . $e->getMessage());
        }
    }

    /**
     * Save or update FCM token for user
     */
    public function saveToken($userId, $token, $deviceType = 'android', $deviceId = null, $appVersion = null)
    {
        try {
            // Check if token already exists for this user
            $existingToken = $this->getTokenByUserAndToken($userId, $token);
            
            if ($existingToken) {
                // Update existing token
                $sql = "UPDATE {$this->table} SET 
                        device_type = :device_type,
                        device_id = :device_id,
                        app_version = :app_version,
                        is_active = TRUE,
                        updated_at = NOW()
                        WHERE user_id = :user_id AND token = :token";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'user_id' => $userId,
                    'token' => $token,
                    'device_type' => $deviceType,
                    'device_id' => $deviceId,
                    'app_version' => $appVersion
                ]);
                
                return [
                    'status' => 'success',
                    'message' => 'Token updated successfully',
                    'action' => 'updated'
                ];
            } else {
                // Insert new token
                $sql = "INSERT INTO {$this->table} 
                        (user_id, token, device_type, device_id, app_version, is_active) 
                        VALUES (:user_id, :token, :device_type, :device_id, :app_version, TRUE)";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'user_id' => $userId,
                    'token' => $token,
                    'device_type' => $deviceType,
                    'device_id' => $deviceId,
                    'app_version' => $appVersion
                ]);
                
                return [
                    'status' => 'success',
                    'message' => 'Token saved successfully',
                    'action' => 'created',
                    'id' => $this->conn->lastInsertId()
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Error saving FCM token: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to save token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get token by user ID and token value
     */
    public function getTokenByUserAndToken($userId, $token)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND token = :token";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId, 'token' => $token]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting FCM token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all active tokens for a user
     */
    public function getUserTokens($userId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id AND is_active = TRUE 
                    ORDER BY updated_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting user FCM tokens: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all tokens for multiple users
     */
    public function getUsersTokens($userIds)
    {
        try {
            if (empty($userIds)) return [];
            
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id IN ($placeholders) AND is_active = TRUE 
                    ORDER BY updated_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($userIds);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting users FCM tokens: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Deactivate token
     */
    public function deactivateToken($userId, $token)
    {
        try {
            $sql = "UPDATE {$this->table} SET is_active = FALSE, updated_at = NOW() 
                    WHERE user_id = :user_id AND token = :token";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId, 'token' => $token]);
            
            return [
                'status' => 'success',
                'message' => 'Token deactivated successfully'
            ];
            
        } catch (PDOException $e) {
            error_log("Error deactivating FCM token: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to deactivate token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete token
     */
    public function deleteToken($userId, $token)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id AND token = :token";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['user_id' => $userId, 'token' => $token]);
            
            return [
                'status' => 'success',
                'message' => 'Token deleted successfully'
            ];
            
        } catch (PDOException $e) {
            error_log("Error deleting FCM token: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to delete token: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get token statistics
     */
    public function getTokenStats()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_tokens,
                        COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_tokens,
                        COUNT(CASE WHEN device_type = 'android' THEN 1 END) as android_tokens,
                        COUNT(CASE WHEN device_type = 'ios' THEN 1 END) as ios_tokens,
                        COUNT(CASE WHEN device_type = 'web' THEN 1 END) as web_tokens
                    FROM {$this->table}";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error getting FCM token stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old inactive tokens
     */
    public function cleanupOldTokens($daysOld = 30)
    {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE is_active = FALSE 
                    AND updated_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['days' => $daysOld]);
            
            $deletedCount = $stmt->rowCount();
            
            return [
                'status' => 'success',
                'message' => "Cleaned up $deletedCount old tokens",
                'deleted_count' => $deletedCount
            ];
            
        } catch (PDOException $e) {
            error_log("Error cleaning up FCM tokens: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => 'Failed to cleanup tokens: ' . $e->getMessage()
            ];
        }
    }
}
