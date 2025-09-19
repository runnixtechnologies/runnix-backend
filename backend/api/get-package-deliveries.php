<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force error logging to a specific file for debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php-error.log');

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Model\PackageDelivery;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Authenticate user
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $packageDeliveryModel = new PackageDelivery();
        
        // Get query parameters
        $status = $_GET['status'] ?? null;
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        
        // Validate pagination parameters
        $page = max(1, (int)$page);
        $limit = min(50, max(1, (int)$limit));
        
        // Validate status if provided
        if ($status && !in_array($status, ['pending', 'accepted', 'picked_up', 'in_transit', 'delivered', 'cancelled'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid status. Valid statuses: pending, accepted, picked_up, in_transit, delivered, cancelled"
            ]);
            exit;
        }
        
        $userId = $user['user_id'];
        
        // Get package deliveries
        $packages = $packageDeliveryModel->getUserPackageDeliveries($userId, $status, $page, $limit);
        
        // Get total count for pagination
        $totalCount = $packageDeliveryModel->getUserPackageCount($userId, $status);
        $totalPages = ceil($totalCount / $limit);
        
        // Format packages for response
        $formattedPackages = [];
        foreach ($packages as $package) {
            $formattedPackages[] = [
                'id' => $package['id'],
                'package_number' => $package['package_number'],
                'status' => $package['status'],
                'receiver_name' => $package['receiver_name'],
                'receiver_phone' => $package['receiver_phone'],
                'receiver_address' => $package['receiver_address'],
                'package_description' => $package['package_description'],
                'package_value' => $package['package_value'],
                'delivery_fee' => $package['delivery_fee'],
                'insurance_fee' => $package['insurance_fee'],
                'total_amount' => $package['delivery_fee'] + $package['insurance_fee'],
                'rider' => $package['rider_id'] ? [
                    'name' => trim($package['rider_first_name'] . ' ' . $package['rider_last_name']),
                    'phone' => $package['rider_phone']
                ] : null,
                'created_at' => $package['created_at'],
                'time_ago' => getTimeAgo($package['created_at']),
                'can_cancel' => in_array($package['status'], ['pending', 'accepted']),
                'can_track' => in_array($package['status'], ['accepted', 'picked_up', 'in_transit'])
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data' => $formattedPackages,
            'meta' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_count' => $totalCount,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]);
        
    } catch (Exception $e) {
        $errorMessage = 'Get package deliveries error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
        error_log($errorMessage, 3, __DIR__ . '/php-error.log');
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving package deliveries."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed. Only GET requests are supported."
    ]);
}

/**
 * Get time ago string
 */
function getTimeAgo($datetime)
{
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?>
