<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;
use Model\UserActivity;

header('Content-Type: application/json');

// Authenticate the request
$user = authenticateRequest();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userActivity = new UserActivity();

// Record user activity
$deviceInfo = json_encode([
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'platform' => 'web',
    'timestamp' => date('Y-m-d H:i:s')
]);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

$activityRecorded = $userActivity->recordActivity(
    $user['user_id'], 
    $user['role'], 
    $deviceInfo, 
    $ipAddress
);

if (!$activityRecorded) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to record activity']);
    exit;
}

// Get session statistics
$sessionStats = $userActivity->getSessionStats($user['user_id']);

if (!$sessionStats) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'No active session found']);
    exit;
}

// Session timeout is disabled - no more inactivity-based auto-logout
// Users stay logged in for 30 days regardless of activity

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Activity updated successfully - No automatic timeout',
    'data' => [
        'session_stats' => $sessionStats,
        'warning' => false, // No warnings since no timeout
        'timeout_minutes' => 'No timeout - 30 days',
        'remaining_minutes' => 'Unlimited',
        'is_expired' => false,
        'note' => 'Sessions no longer timeout automatically. Users stay logged in until manual logout or 30-day expiry.'
    ]
]);
