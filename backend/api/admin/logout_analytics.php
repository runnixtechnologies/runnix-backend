<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;
use function Middleware\authorizeRoles;
use Model\LogoutLog;

header('Content-Type: application/json');

// Authenticate the request
$user = authenticateRequest();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Check if user is admin
authorizeRoles(['admin'], $user['role']);

$logoutLog = new LogoutLog();

// Get query parameters
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;

// Validate parameters
if ($days < 1 || $days > 365) {
    $days = 30;
}
if ($hours < 1 || $hours > 168) { // Max 1 week
    $hours = 24;
}

try {
    // Get system-wide logout analytics
    $analytics = $logoutLog->getSystemLogoutAnalytics($days);
    
    // Get suspicious logout patterns
    $suspicious = $logoutLog->getSuspiciousLogouts($hours);
    
    if ($analytics === false || $suspicious === false) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to retrieve analytics data'
        ]);
        exit;
    }
    
    // Calculate summary statistics
    $totalLogouts = 0;
    $totalUsers = 0;
    $avgSessionDuration = 0;
    $sessionCount = 0;
    
    foreach ($analytics as $role) {
        $totalLogouts += $role['total_logouts'];
        $totalUsers += $role['unique_users'];
        if ($role['avg_session_duration']) {
            $avgSessionDuration += $role['avg_session_duration'];
            $sessionCount++;
        }
    }
    
    $overallAvgSession = $sessionCount > 0 ? $avgSessionDuration / $sessionCount : 0;
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Logout analytics retrieved successfully',
        'data' => [
            'period_days' => $days,
            'suspicious_hours' => $hours,
            'summary' => [
                'total_logouts' => $totalLogouts,
                'total_unique_users' => $totalUsers,
                'overall_avg_session_duration' => round($overallAvgSession, 2),
                'suspicious_activity_count' => count($suspicious)
            ],
            'analytics_by_role' => $analytics,
            'suspicious_activity' => $suspicious
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
