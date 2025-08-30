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

// Check if session is about to expire (warning threshold: 5 minutes)
$warningThreshold = 5; // minutes
$isWarning = $sessionStats['remaining_minutes'] <= $warningThreshold;

// Determine session status
$status = 'active';
if ($sessionStats['is_expired']) {
    $status = 'expired';
} elseif ($isWarning) {
    $status = 'warning';
}

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Session status retrieved successfully',
    'data' => [
        'session_status' => $status,
        'session_stats' => $sessionStats,
        'warning' => $isWarning,
        'timeout_minutes' => $sessionStats['timeout_minutes'],
        'remaining_minutes' => round($sessionStats['remaining_minutes'], 1),
        'is_expired' => $sessionStats['is_expired'],
        'user_role' => $user['role'],
        'session_start' => $sessionStats['session_start'],
        'last_activity' => $sessionStats['last_activity']
    ]
]);
