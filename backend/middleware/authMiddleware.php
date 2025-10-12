<?php

namespace Middleware;

use Config\JwtHandler;
use Controller\DeviceController;

function authenticateRequest($forceValidate = false) {
    $headers = getallheaders();

    // Handle case where getallheaders() might not work
    if (!$headers) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
    }

    $authHeader = $headers['Authorization'] ?? null;
    $token = null;
    $logFile = __DIR__ . '/../php-error.log'; // points to backend/php-error.log

    // Primary: Authorization header (Bearer ...)
    if ($authHeader) {
        $token = str_replace('Bearer ', '', $authHeader);
    } else {
        // Fallback for some hosts that strip Authorization on GET
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($requestMethod) === 'GET' && isset($_GET['token']) && is_string($_GET['token']) && $_GET['token'] !== '') {
            $token = $_GET['token'];
        }
    }

    if (!$token) {
        $msg = "[AUTH] Missing token. Method=" . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') .
               " URI=" . ($_SERVER['REQUEST_URI'] ?? 'N/A');
        error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, $logFile);

        http_response_code(401);
        echo json_encode([
            "status" => "error",
            "message" => "Authorization token missing",
            "debug" => "Provide header Authorization: Bearer <token> or ?token=<token> for GET"
        ]);
        exit;
    }

    $jwt = new JwtHandler();
    $decoded = $jwt->decode($token);
    if (!$decoded) {
        $msg = "[AUTH] Invalid or expired token. Token=$token";
        error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, $logFile);

        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
        exit;
    }

    // Log successful authentication
    $msg = "[AUTH] Success for user_id=" . ($decoded['user_id'] ?? 'unknown') .
           " role=" . ($decoded['role'] ?? 'unknown');
    error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, $logFile);

    // Auto-register device if device data is available
    try {
        autoRegisterDevice($decoded);
    } catch (Exception $e) {
        // Don't fail authentication if device registration fails
        error_log("[" . date('Y-m-d H:i:s') . "] Auto device registration failed: " . $e->getMessage(), 3, __DIR__ . '/../php-error.log');
    }

    // Return decoded payload (e.g., ['user_id' => ..., 'role' => ...])
    if(!$decoded)
        abort(401, "Unauthenticated");
    return $decoded;
}

function autoRegisterDevice($user) {
    $headers = getallheaders();
    $requestData = [];
    $deviceData = [];
    
    // Check for mobile app device data in headers
    $hasMobileDeviceData = false;
    if (isset($headers['X-Device-ID']) || isset($headers['X-Device-Type'])) {
        $hasMobileDeviceData = true;
    }
    
    // Check for mobile app device data in POST body
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        if (isset($input['device_id'])) {
            $hasMobileDeviceData = true;
            $requestData = $input;
        }
    }
    
    try {
        $deviceController = new DeviceController();
        
        if ($hasMobileDeviceData) {
            // Mobile app - extract full device data
            $deviceData = $deviceController->extractDeviceData($requestData);
        } else {
            // Web/Postman - collect basic info automatically
            $deviceData = collectBasicDeviceInfo();
        }
        
        if (!empty($deviceData['device_id'])) {
            $deviceController->registerDevice($user, $deviceData);
        }
    } catch (Exception $e) {
        error_log("[" . date('Y-m-d H:i:s') . "] Auto device registration error: " . $e->getMessage(), 3, __DIR__ . '/../php-error.log');
    }
}

function collectBasicDeviceInfo() {
    // Collect basic device info automatically for web/Postman requests
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Generate a basic device ID from IP + User Agent hash
    $deviceId = 'web_' . md5($ipAddress . $userAgent);
    
    // Detect basic device type from User Agent
    $deviceType = 'web';
    if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false || strpos($userAgent, 'iPhone') !== false) {
        $deviceType = 'mobile_web';
    }
    
    // Extract basic OS info from User Agent
    $osVersion = 'unknown';
    if (preg_match('/Windows NT ([0-9.]+)/', $userAgent, $matches)) {
        $osVersion = 'Windows ' . $matches[1];
    } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
        $osVersion = 'macOS ' . str_replace('_', '.', $matches[1]);
    } elseif (preg_match('/Linux/', $userAgent)) {
        $osVersion = 'Linux';
    }
    
    return [
        'device_id' => $deviceId,
        'device_type' => $deviceType,
        'device_model' => 'Web Browser',
        'os_version' => $osVersion,
        'app_version' => '1.0.0',
        'screen_resolution' => null,
        'network_type' => null,
        'carrier_name' => null,
        'timezone' => date_default_timezone_get(),
        'language' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'en',
        'locale' => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5) : 'en-US',
        'user_agent' => $userAgent,
        'ip_address' => $ipAddress
    ];
}

function authorizeRoles(array $allowedRoles, $userRole) {
    $logFile = __DIR__ . '/../php-error.log';

    if (!in_array($userRole, $allowedRoles)) {
        $msg = "[AUTH] Access denied. Role=$userRole, Allowed=" . implode(',', $allowedRoles);
        error_log("[" . date('Y-m-d H:i:s') . "] $msg\n", 3, $logFile);

        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Access denied for your role"]);
        exit;
    }
}
