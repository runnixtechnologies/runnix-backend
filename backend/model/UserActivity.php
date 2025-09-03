<?php
namespace Model;

use Config\Database;
use PDO;

class UserActivity
{
    private $conn;
    private $table = "user_activity";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Get inactivity timeout in minutes based on user role
     */
    public function getInactivityTimeout($role)
    {
        $timeouts = [
            'user' => 30,      // 30 minutes for customers
            'merchant' => 45,  // 45 minutes for merchants
            'rider' => 20      // 20 minutes for riders
        ];

        return $timeouts[$role] ?? 30; // Default to 30 minutes
    }

    /**
     * Record or update user activity
     */
    public function recordActivity($userId, $role, $deviceInfo = null, $ipAddress = null)
    {
        try {
            // First, deactivate any existing sessions for this user
            $this->deactivateAllUserSessions($userId);
            
            // Create new session
            $sql = "INSERT INTO {$this->table} 
                    (user_id, user_role, device_info, ip_address, is_active, session_start, last_activity) 
                    VALUES (:user_id, :user_role, :device_info, :ip_address, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':user_role', $role);
            $stmt->bindParam(':device_info', $deviceInfo);
            $stmt->bindParam(':ip_address', $ipAddress);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error recording user activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active session for user
     */
    public function getActiveSession($userId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND is_active = TRUE 
                ORDER BY last_activity DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user session has expired due to inactivity
     */
    public function isSessionExpired($userId, $role)
    {
        $session = $this->getActiveSession($userId);
        
        if (!$session) {
            return true; // No active session means expired
        }

        $timeout = $this->getInactivityTimeout($role);
        $lastActivity = strtotime($session['last_activity']);
        $currentTime = time();
        $timeDiff = ($currentTime - $lastActivity) / 60; // Convert to minutes

        return $timeDiff > $timeout;
    }

    /**
     * Get remaining session time in minutes
     */
    public function getRemainingSessionTime($userId, $role)
    {
        $session = $this->getActiveSession($userId);
        
        if (!$session) {
            return 0;
        }

        $timeout = $this->getInactivityTimeout($role);
        $lastActivity = strtotime($session['last_activity']);
        $currentTime = time();
        $timeDiff = ($currentTime - $lastActivity) / 60; // Convert to minutes

        return max(0, $timeout - $timeDiff);
    }

    /**
     * Deactivate user session (logout)
     */
    public function deactivateSession($userId)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET is_active = FALSE 
                    WHERE user_id = :user_id AND is_active = TRUE";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            
            $result = $stmt->execute();
            
            // Log the operation for debugging
            $rowCount = $stmt->rowCount();
            error_log("Deactivated {$rowCount} active sessions for user {$userId}");
            
            return $result;
        } catch (\Exception $e) {
            error_log("Error deactivating session for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deactivate ALL sessions for a user (used before creating new session)
     */
    public function deactivateAllUserSessions($userId)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET is_active = FALSE 
                    WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            
            $result = $stmt->execute();
            
            // Log the operation for debugging
            $rowCount = $stmt->rowCount();
            error_log("Deactivated {$rowCount} total sessions for user {$userId} before creating new session");
            
            return $result;
        } catch (\Exception $e) {
            error_log("Error deactivating all sessions for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get session statistics
     */
    public function getSessionStats($userId)
    {
        $session = $this->getActiveSession($userId);
        
        if (!$session) {
            return null;
        }

        $role = $session['user_role'];
        $timeout = $this->getInactivityTimeout($role);
        $remainingTime = $this->getRemainingSessionTime($userId, $role);
        $sessionDuration = (time() - strtotime($session['session_start'])) / 60; // minutes

        return [
            'session_start' => $session['session_start'],
            'last_activity' => $session['last_activity'],
            'timeout_minutes' => $timeout,
            'remaining_minutes' => $remainingTime,
            'session_duration_minutes' => round($sessionDuration, 2),
            'is_expired' => $remainingTime <= 0
        ];
    }

    /**
     * Clean up expired sessions (called by cron job or manually)
     */
    public function cleanupExpiredSessions()
    {
        $sql = "UPDATE {$this->table} ua
                JOIN (
                    SELECT user_id, user_role, 
                           TIMESTAMPDIFF(MINUTE, last_activity, NOW()) as minutes_inactive
                    FROM {$this->table}
                    WHERE is_active = TRUE
                ) as inactive ON ua.user_id = inactive.user_id
                SET ua.is_active = FALSE
                WHERE inactive.minutes_inactive > 
                    CASE inactive.user_role
                        WHEN 'user' THEN 30
                        WHEN 'merchant' THEN 45
                        WHEN 'rider' THEN 20
                        ELSE 30
                    END";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute();
    }
}
