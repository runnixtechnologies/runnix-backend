<?php
namespace Model;

use Config\Database;
use PDO;
use PDOException;

class Device
{
    private $conn;
    private $table = "user_devices";

    public function __construct()
    {
        $this->conn = (new Database())->getConnection();
    }

    /**
     * Register or update device information
     */
    public function registerDevice($userId, $userType, $deviceData)
    {
        try {
            $this->conn->beginTransaction();

            // Check if device already exists for this user
            $existingDevice = $this->getDeviceByUserAndDeviceId($userId, $deviceData['device_id']);

            if ($existingDevice) {
                // Update existing device
                $this->updateDevice($existingDevice['id'], $deviceData);
                $deviceId = $existingDevice['id'];
            } else {
                // Create new device record
                $deviceId = $this->createDevice($userId, $userType, $deviceData);
            }

            $this->conn->commit();
            return $deviceId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("[" . date('Y-m-d H:i:s') . "] Device registration error: " . $e->getMessage() . " | User ID: " . $userId . " | Device ID: " . ($deviceData['device_id'] ?? 'unknown'), 3, __DIR__ . '/../php-error.log');
            return false;
        }
    }

    /**
     * Create new device record
     */
    private function createDevice($userId, $userType, $deviceData)
    {
        $sql = "INSERT INTO {$this->table} 
                (user_id, user_type, device_id, device_type, device_model, os_version, 
                 app_version, screen_resolution, network_type, carrier_name, timezone, 
                 language, locale, is_active, last_active_at, first_seen_at) 
                VALUES (:user_id, :user_type, :device_id, :device_type, :device_model, :os_version, 
                        :app_version, :screen_resolution, :network_type, :carrier_name, :timezone, 
                        :language, :locale, :is_active, NOW(), NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':user_type' => $userType,
            ':device_id' => $deviceData['device_id'],
            ':device_type' => $deviceData['device_type'] ?? 'unknown',
            ':device_model' => $deviceData['device_model'] ?? null,
            ':os_version' => $deviceData['os_version'] ?? null,
            ':app_version' => $deviceData['app_version'] ?? null,
            ':screen_resolution' => $deviceData['screen_resolution'] ?? null,
            ':network_type' => $deviceData['network_type'] ?? null,
            ':carrier_name' => $deviceData['carrier_name'] ?? null,
            ':timezone' => $deviceData['timezone'] ?? null,
            ':language' => $deviceData['language'] ?? null,
            ':locale' => $deviceData['locale'] ?? null,
            ':is_active' => true
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Update existing device record
     */
    private function updateDevice($deviceId, $deviceData)
    {
        $sql = "UPDATE {$this->table} SET 
                device_type = :device_type,
                device_model = :device_model,
                os_version = :os_version,
                app_version = :app_version,
                screen_resolution = :screen_resolution,
                network_type = :network_type,
                carrier_name = :carrier_name,
                timezone = :timezone,
                language = :language,
                locale = :locale,
                is_active = :is_active,
                last_active_at = NOW(),
                updated_at = NOW()
                WHERE id = :device_id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':device_id' => $deviceId,
            ':device_type' => $deviceData['device_type'] ?? 'unknown',
            ':device_model' => $deviceData['device_model'] ?? null,
            ':os_version' => $deviceData['os_version'] ?? null,
            ':app_version' => $deviceData['app_version'] ?? null,
            ':screen_resolution' => $deviceData['screen_resolution'] ?? null,
            ':network_type' => $deviceData['network_type'] ?? null,
            ':carrier_name' => $deviceData['carrier_name'] ?? null,
            ':timezone' => $deviceData['timezone'] ?? null,
            ':language' => $deviceData['language'] ?? null,
            ':locale' => $deviceData['locale'] ?? null,
            ':is_active' => true
        ]);

        return true;
    }

    /**
     * Get device by user ID and device ID
     */
    public function getDeviceByUserAndDeviceId($userId, $deviceId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND device_id = :device_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':device_id' => $deviceId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all devices for a user
     */
    public function getUserDevices($userId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY last_active_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get active devices for a user
     */
    public function getActiveUserDevices($userId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id AND is_active = 1 
                ORDER BY last_active_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Deactivate device
     */
    public function deactivateDevice($userId, $deviceId)
    {
        $sql = "UPDATE {$this->table} SET 
                is_active = 0, 
                updated_at = NOW() 
                WHERE user_id = :user_id AND device_id = :device_id";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':device_id' => $deviceId
        ]);
    }

    /**
     * Get device statistics
     */
    public function getDeviceStats($userId = null)
    {
        $sql = "SELECT 
                    user_type,
                    device_type,
                    COUNT(*) as device_count,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_count
                FROM {$this->table}";
        
        $params = [];
        if ($userId) {
            $sql .= " WHERE user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " GROUP BY user_type, device_type";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get connection for external use
     */
    public function getConnection()
    {
        return $this->conn;
    }
}
?>
