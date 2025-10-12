<?php
namespace Model;

use Config\Database;
use PDO;

class RateLimiter
{
    private $conn;
    private $table = "rate_limits";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Check if a request is allowed based on rate limits
     * 
     * @param string $identifier Phone, email, or IP address
     * @param string $identifierType Type of identifier (phone, email, ip)
     * @param string $action Action being rate limited (e.g., send_otp)
     * @param int $maxRequests Maximum requests allowed in the window
     * @param int $windowDuration Window duration in seconds
     * @param int $blockDuration Block duration in seconds if limit exceeded
     * @return array Rate limit check result
     */
    public function checkRateLimit($identifier, $identifierType, $action, $maxRequests = 5, $windowDuration = 3600, $blockDuration = 3600)
    {
        try {
            $now = new \DateTime();
            $windowStart = $now->getTimestamp() - $windowDuration;
            
            // Check if currently blocked
            $blockCheckSql = "SELECT blocked_until FROM {$this->table} 
                            WHERE identifier = :identifier 
                            AND identifier_type = :identifier_type 
                            AND action = :action 
                            AND blocked_until > NOW() 
                            ORDER BY blocked_until DESC 
                            LIMIT 1";
            
            $blockStmt = $this->conn->prepare($blockCheckSql);
            $blockStmt->execute([
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
                'action' => $action
            ]);
            
            $blockResult = $blockStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($blockResult && $blockResult['blocked_until']) {
                $blockedUntil = new \DateTime($blockResult['blocked_until']);
                $remainingSeconds = $blockedUntil->getTimestamp() - $now->getTimestamp();
                
                return [
                    'allowed' => false,
                    'reason' => 'blocked',
                    'blocked_until' => $blockResult['blocked_until'],
                    'remaining_seconds' => max(0, $remainingSeconds),
                    'message' => "Too many requests. Try again in " . $this->formatTime($remainingSeconds)
                ];
            }
            
            // Count requests in current window
            $countSql = "SELECT COUNT(*) as request_count FROM {$this->table} 
                        WHERE identifier = :identifier 
                        AND identifier_type = :identifier_type 
                        AND action = :action 
                        AND window_start >= FROM_UNIXTIME(:window_start)";
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute([
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
                'action' => $action,
                'window_start' => $windowStart
            ]);
            
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            $currentCount = (int)$countResult['request_count'];
            
            if ($currentCount >= $maxRequests) {
                // Block the identifier
                $blockUntil = $now->getTimestamp() + $blockDuration;
                $this->blockIdentifier($identifier, $identifierType, $action, $blockUntil);
                
                return [
                    'allowed' => false,
                    'reason' => 'limit_exceeded',
                    'current_count' => $currentCount,
                    'max_requests' => $maxRequests,
                    'blocked_until' => date('Y-m-d H:i:s', $blockUntil),
                    'remaining_seconds' => $blockDuration,
                    'message' => "Rate limit exceeded. Try again in " . $this->formatTime($blockDuration)
                ];
            }
            
            // Record this request
            $this->recordRequest($identifier, $identifierType, $action, $windowDuration);
            
            return [
                'allowed' => true,
                'current_count' => $currentCount + 1,
                'max_requests' => $maxRequests,
                'remaining_requests' => $maxRequests - ($currentCount + 1),
                'window_duration' => $windowDuration,
                'message' => "Request allowed"
            ];
            
        } catch (\Exception $e) {
            error_log("RateLimiter error: " . $e->getMessage());
            return [
                'allowed' => false,
                'reason' => 'error',
                'message' => 'Rate limit check failed'
            ];
        }
    }

    /**
     * Record a request in the rate limit tracking
     */
    private function recordRequest($identifier, $identifierType, $action, $windowDuration)
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (identifier, identifier_type, action, request_count, window_start, window_duration) 
                    VALUES (:identifier, :identifier_type, :action, 1, NOW(), :window_duration)
                    ON DUPLICATE KEY UPDATE 
                    request_count = request_count + 1,
                    updated_at = NOW()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
                'action' => $action,
                'window_duration' => $windowDuration
            ]);
            
        } catch (\Exception $e) {
            error_log("RateLimiter recordRequest error: " . $e->getMessage());
        }
    }

    /**
     * Block an identifier for a specified duration
     */
    private function blockIdentifier($identifier, $identifierType, $action, $blockUntil)
    {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (identifier, identifier_type, action, request_count, window_start, window_duration, blocked_until) 
                    VALUES (:identifier, :identifier_type, :action, 1, NOW(), 3600, FROM_UNIXTIME(:block_until))
                    ON DUPLICATE KEY UPDATE 
                    blocked_until = FROM_UNIXTIME(:block_until),
                    updated_at = NOW()";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
                'action' => $action,
                'block_until' => $blockUntil
            ]);
            
        } catch (\Exception $e) {
            error_log("RateLimiter blockIdentifier error: " . $e->getMessage());
        }
    }

    /**
     * Get rate limit status for an identifier
     */
    public function getRateLimitStatus($identifier, $identifierType, $action)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as request_count,
                        MAX(window_start) as last_request,
                        MAX(blocked_until) as blocked_until
                    FROM {$this->table} 
                    WHERE identifier = :identifier 
                    AND identifier_type = :identifier_type 
                    AND action = :action 
                    AND window_start >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'identifier' => $identifier,
                'identifier_type' => $identifierType,
                'action' => $action
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'request_count' => (int)$result['request_count'],
                'last_request' => $result['last_request'],
                'blocked_until' => $result['blocked_until'],
                'is_blocked' => $result['blocked_until'] && $result['blocked_until'] > date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            error_log("RateLimiter getRateLimitStatus error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clean up old rate limit records
     */
    public function cleanupOldRecords($olderThanHours = 24)
    {
        try {
            $sql = "DELETE FROM {$this->table} 
                    WHERE window_start < DATE_SUB(NOW(), INTERVAL :hours HOUR)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['hours' => $olderThanHours]);
            
            return $stmt->rowCount();
            
        } catch (\Exception $e) {
            error_log("RateLimiter cleanupOldRecords error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Format seconds into human readable time
     */
    private function formatTime($seconds)
    {
        if ($seconds < 60) {
            return $seconds . " seconds";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            return $minutes . " minute" . ($minutes > 1 ? "s" : "");
        } else {
            $hours = floor($seconds / 3600);
            return $hours . " hour" . ($hours > 1 ? "s" : "");
        }
    }

    /**
     * Get client IP address
     */
    public static function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
