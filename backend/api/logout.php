<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';
require_once '../config/JwtHandler.php';

use function Middleware\authenticateRequest;
use Config\JwtHandler;
use Model\UserActivity;
use Model\LogoutLog;

header('Content-Type: application/json');

$user = authenticateRequest();

// Get the authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Authorization header is required']);
    exit;
}

$token = $matches[1];

// Blacklist the token for immediate logout
$jwt = new JwtHandler();
$blacklisted = $jwt->blacklistToken($token);

// Deactivate user session
$userActivity = new UserActivity();
try {
    $sessionDeactivated = $userActivity->deactivateSession($user['user_id']);
    if (!$sessionDeactivated) {
        error_log("Warning: Failed to deactivate session for user " . $user['user_id']);
    }
} catch (Exception $e) {
    error_log("Error deactivating session: " . $e->getMessage());
    $sessionDeactivated = false;
}

// Get session statistics for logging
$sessionStats = $userActivity->getSessionStats($user['user_id']);
$sessionDuration = $sessionStats ? $sessionStats['session_duration_minutes'] : 0;

// Log the logout event
$logoutLogModel = new LogoutLog();
$logoutData = [
    'user_id' => $user['user_id'],
    'user_role' => $user['role'],
    'logout_type' => 'manual',
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'device_info' => json_encode([
        'platform' => 'web',
        'timestamp' => date('Y-m-d H:i:s')
    ]),
    'session_duration_minutes' => $sessionDuration,
    'token_blacklisted' => $blacklisted,
    'session_deactivated' => $sessionDeactivated,
    'logout_reason' => 'User initiated logout'
];

$logoutLogged = $logoutLogModel->logLogout($logoutData);

// Prepare response data
$responseData = [
    'status' => 'success',
    'message' => 'Logged out successfully',
    'data' => [
        'user_id' => $user['user_id'],
        'role' => $user['role'],
        'logout_time' => date('Y-m-d H:i:s'),
        'token_blacklisted' => $blacklisted,
        'session_deactivated' => $sessionDeactivated,
        'session_duration_minutes' => $sessionDuration,
        'logout_logged' => $logoutLogged
    ]
];

if ($blacklisted && $sessionDeactivated) {
    http_response_code(200);
    echo json_encode($responseData);
} else {
    // Even if one operation fails, still return success but log the issue
    $responseData['message'] = 'Logged out with some cleanup issues';
    $responseData['data']['partial_success'] = true;
    
    error_log("Partial logout success - Token blacklisted: " . ($blacklisted ? 'yes' : 'no') . 
              ", Session deactivated: " . ($sessionDeactivated ? 'yes' : 'no'));

http_response_code(200);
    echo json_encode($responseData);
}
