<?php
namespace Controller;

use Model\Device;
use Exception;

class DeviceController
{
    private $deviceModel;

    public function __construct()
    {
        $this->deviceModel = new Device();
    }

    /**
     * Register device automatically (called from auth middleware)
     */
    public function registerDevice($user, $deviceData)
    {
        try {
            // Determine user type based on user data
            $userType = $this->determineUserType($user);
            
            // Validate required device data
            if (empty($deviceData['device_id'])) {
                error_log("Device registration failed: device_id is required");
                return false;
            }

            // Register/update device
            $deviceId = $this->deviceModel->registerDevice($user['user_id'], $userType, $deviceData);
            
            if ($deviceId) {
                error_log("Device registered successfully for user {$user['user_id']}: {$deviceData['device_id']}");
                return true;
            } else {
                error_log("Device registration failed for user {$user['user_id']}");
                return false;
            }

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Device registration error: " . $e->getMessage() . " | User ID: " . $user['user_id'] . " | Device ID: " . ($deviceData['device_id'] ?? 'unknown'), 3, __DIR__ . '/../php-error.log');
            return false;
        }
    }

    /**
     * Get user's devices
     */
    public function getUserDevices($user)
    {
        try {
            $devices = $this->deviceModel->getActiveUserDevices($user['user_id']);
            
            // Format response
            $formattedDevices = [];
            foreach ($devices as $device) {
                $formattedDevices[] = [
                    'id' => $device['id'],
                    'device_id' => $device['device_id'],
                    'device_type' => $device['device_type'],
                    'device_model' => $device['device_model'],
                    'os_version' => $device['os_version'],
                    'app_version' => $device['app_version'],
                    'is_active' => (bool)$device['is_active'],
                    'last_active' => $device['last_active_at'],
                    'first_seen' => $device['first_seen_at']
                ];
            }

            return [
                'status' => 'success',
                'data' => $formattedDevices
            ];

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Get user devices error: " . $e->getMessage() . " | User ID: " . $user['user_id'], 3, __DIR__ . '/../php-error.log');
            return [
                'status' => 'error',
                'message' => 'Failed to get user devices'
            ];
        }
    }

    /**
     * Deactivate device
     */
    public function deactivateDevice($user, $deviceId)
    {
        try {
            $result = $this->deviceModel->deactivateDevice($user['user_id'], $deviceId);
            
            if ($result) {
                return [
                    'status' => 'success',
                    'message' => 'Device deactivated successfully'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to deactivate device'
                ];
            }

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Deactivate device error: " . $e->getMessage() . " | User ID: " . $user['user_id'] . " | Device ID: " . $deviceId, 3, __DIR__ . '/../php-error.log');
            return [
                'status' => 'error',
                'message' => 'Failed to deactivate device'
            ];
        }
    }

    /**
     * Get device statistics (admin only)
     */
    public function getDeviceStats($user = null)
    {
        try {
            $stats = $this->deviceModel->getDeviceStats($user ? $user['user_id'] : null);
            
            return [
                'status' => 'success',
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("[" . date('Y-m-d H:i:s') . "] Get device stats error: " . $e->getMessage(), 3, __DIR__ . '/../php-error.log');
            return [
                'status' => 'error',
                'message' => 'Failed to get device statistics'
            ];
        }
    }

    /**
     * Determine user type from user data
     */
    private function determineUserType($user)
    {
        // Check if user has role field
        if (isset($user['role'])) {
            switch ($user['role']) {
                case 'user':
                    return 'customer';
                case 'merchant':
                    return 'merchant';
                case 'rider':
                    return 'rider';
                default:
                    return 'customer';
            }
        }

        // Fallback: check if user has store (merchant) or is rider
        if (isset($user['has_store']) && $user['has_store']) {
            return 'merchant';
        }

        if (isset($user['is_rider']) && $user['is_rider']) {
            return 'rider';
        }

        // Default to customer
        return 'customer';
    }

    /**
     * Extract device data from request headers and body
     */
    public function extractDeviceData($requestData = [])
    {
        $deviceData = [];

        // Extract from request body (mobile app sends this)
        if (isset($requestData['device_id'])) {
            $deviceData['device_id'] = $requestData['device_id'];
        }
        if (isset($requestData['device_type'])) {
            $deviceData['device_type'] = $requestData['device_type'];
        }
        if (isset($requestData['device_model'])) {
            $deviceData['device_model'] = $requestData['device_model'];
        }
        if (isset($requestData['os_version'])) {
            $deviceData['os_version'] = $requestData['os_version'];
        }
        if (isset($requestData['app_version'])) {
            $deviceData['app_version'] = $requestData['app_version'];
        }
        if (isset($requestData['screen_resolution'])) {
            $deviceData['screen_resolution'] = $requestData['screen_resolution'];
        }
        if (isset($requestData['network_type'])) {
            $deviceData['network_type'] = $requestData['network_type'];
        }
        if (isset($requestData['carrier_name'])) {
            $deviceData['carrier_name'] = $requestData['carrier_name'];
        }
        if (isset($requestData['timezone'])) {
            $deviceData['timezone'] = $requestData['timezone'];
        }
        if (isset($requestData['language'])) {
            $deviceData['language'] = $requestData['language'];
        }
        if (isset($requestData['locale'])) {
            $deviceData['locale'] = $requestData['locale'];
        }

        // Extract from headers (if available)
        $headers = getallheaders();
        if (isset($headers['X-Device-ID'])) {
            $deviceData['device_id'] = $headers['X-Device-ID'];
        }
        if (isset($headers['X-Device-Type'])) {
            $deviceData['device_type'] = $headers['X-Device-Type'];
        }
        if (isset($headers['X-App-Version'])) {
            $deviceData['app_version'] = $headers['X-App-Version'];
        }

        return $deviceData;
    }
}
?>
