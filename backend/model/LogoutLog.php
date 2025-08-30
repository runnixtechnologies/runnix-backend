<?php
namespace Model;

use Config\Database;
use PDO;

class LogoutLog
{
    private $conn;
    private $table = "logout_logs";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Log a logout event
     */
    public function logLogout($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (user_id, user_role, logout_type, ip_address, user_agent, 
                     device_info, session_duration_minutes, token_blacklisted, 
                     session_deactivated, logout_reason) 
                    VALUES (:user_id, :user_role, :logout_type, :ip_address, :user_agent,
                            :device_info, :session_duration_minutes, :token_blacklisted,
                            :session_deactivated, :logout_reason)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $data['user_id']);
            $stmt->bindParam(':user_role', $data['user_role']);
            $stmt->bindParam(':logout_type', $data['logout_type']);
            $stmt->bindParam(':ip_address', $data['ip_address']);
            $stmt->bindParam(':user_agent', $data['user_agent']);
            $stmt->bindParam(':device_info', $data['device_info']);
            $stmt->bindParam(':session_duration_minutes', $data['session_duration_minutes']);
            $stmt->bindParam(':token_blacklisted', $data['token_blacklisted']);
            $stmt->bindParam(':session_deactivated', $data['session_deactivated']);
            $stmt->bindParam(':logout_reason', $data['logout_reason']);

            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error logging logout: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get logout statistics for a user
     */
    public function getUserLogoutStats($userId, $days = 30)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_logouts,
                        COUNT(CASE WHEN logout_type = 'manual' THEN 1 END) as manual_logouts,
                        COUNT(CASE WHEN logout_type = 'auto' THEN 1 END) as auto_logouts,
                        COUNT(CASE WHEN logout_type = 'inactivity' THEN 1 END) as inactivity_logouts,
                        COUNT(CASE WHEN logout_type = 'security' THEN 1 END) as security_logouts,
                        AVG(session_duration_minutes) as avg_session_duration,
                        MAX(logout_time) as last_logout
                    FROM {$this->table} 
                    WHERE user_id = :user_id 
                    AND logout_time >= DATE_SUB(NOW(), INTERVAL :days DAY)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':days', $days);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting user logout stats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent logout history for a user
     */
    public function getUserLogoutHistory($userId, $limit = 10)
    {
        try {
            $sql = "SELECT 
                        logout_time,
                        logout_type,
                        ip_address,
                        session_duration_minutes,
                        logout_reason
                    FROM {$this->table} 
                    WHERE user_id = :user_id 
                    ORDER BY logout_time DESC 
                    LIMIT :limit";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting user logout history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get system-wide logout analytics
     */
    public function getSystemLogoutAnalytics($days = 30)
    {
        try {
            $sql = "SELECT 
                        user_role,
                        COUNT(*) as total_logouts,
                        COUNT(CASE WHEN logout_type = 'manual' THEN 1 END) as manual_logouts,
                        COUNT(CASE WHEN logout_type = 'inactivity' THEN 1 END) as inactivity_logouts,
                        AVG(session_duration_minutes) as avg_session_duration,
                        COUNT(DISTINCT user_id) as unique_users
                    FROM {$this->table} 
                    WHERE logout_time >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    GROUP BY user_role";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':days', $days);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting system logout analytics: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get suspicious logout patterns (multiple logouts from different IPs in short time)
     */
    public function getSuspiciousLogouts($hours = 24)
    {
        try {
            $sql = "SELECT 
                        user_id,
                        user_role,
                        COUNT(DISTINCT ip_address) as unique_ips,
                        COUNT(*) as logout_count,
                        MIN(logout_time) as first_logout,
                        MAX(logout_time) as last_logout
                    FROM {$this->table} 
                    WHERE logout_time >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                    GROUP BY user_id, user_role
                    HAVING unique_ips > 2 AND logout_count > 3";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':hours', $hours);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Error getting suspicious logouts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up old logout logs
     */
    public function cleanupOldLogs($days = 90)
    {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE logout_time < DATE_SUB(NOW(), INTERVAL :days DAY)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':days', $days);
            
            return $stmt->execute();
        } catch (\Exception $e) {
            error_log("Error cleaning up old logout logs: " . $e->getMessage());
            return false;
        }
    }
}
