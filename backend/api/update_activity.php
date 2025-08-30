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

// Check if session is about to expire (warning threshold: 5 minutes)
$warningThreshold = 5; // minutes
$isWarning = $sessionStats['remaining_minutes'] <= $warningThreshold;

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Activity updated successfully',
    'data' => [
        'session_stats' => $sessionStats,
        'warning' => $isWarning,
        'timeout_minutes' => $sessionStats['timeout_minutes'],
        'remaining_minutes' => $sessionStats['remaining_minutes'],
        'is_expired' => $sessionStats['is_expired']
    ]
]);
