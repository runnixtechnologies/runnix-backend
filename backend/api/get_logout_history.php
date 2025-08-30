<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;
use Model\LogoutLog;

header('Content-Type: application/json');

// Authenticate the request
$user = authenticateRequest();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$logoutLog = new LogoutLog();

// Get query parameters
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validate parameters
if ($days < 1 || $days > 365) {
    $days = 30;
}
if ($limit < 1 || $limit > 100) {
    $limit = 10;
}

try {
    // Get user logout statistics
    $stats = $logoutLog->getUserLogoutStats($user['user_id'], $days);
    
    // Get recent logout history
    $history = $logoutLog->getUserLogoutHistory($user['user_id'], $limit);
    
    if ($stats === false || $history === false) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to retrieve logout data'
        ]);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Logout history retrieved successfully',
        'data' => [
            'user_id' => $user['user_id'],
            'role' => $user['role'],
            'period_days' => $days,
            'statistics' => $stats,
            'recent_history' => $history
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}
