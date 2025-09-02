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

// Get session statistics
$sessionStats = $userActivity->getSessionStats($user['user_id']);

if (!$sessionStats) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'No active session found']);
    exit;
}

// Session timeout is disabled - users stay logged in for 30 days
// No more inactivity-based auto-logout
$status = 'active';
$isWarning = false; // No warnings since no timeout

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Session status retrieved successfully - No automatic timeout',
    'data' => [
        'session_status' => $status,
        'session_stats' => $sessionStats,
        'warning' => $isWarning,
        'timeout_minutes' => 'No timeout - 30 days',
        'remaining_minutes' => 'Unlimited',
        'is_expired' => false,
        'user_role' => $user['role'],
        'session_start' => $sessionStats['session_start'],
        'last_activity' => $sessionStats['last_activity'],
        'note' => 'Sessions no longer timeout automatically. Users stay logged in until manual logout or 30-day expiry.'
    ]
]);
