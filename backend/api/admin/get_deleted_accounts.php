<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';

use Model\User;
use Middleware;

header('Content-Type: application/json');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Only GET requests are accepted."]);
    exit;
}

// Authenticate the request
try {
    $user = Middleware\authenticateRequest();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authentication failed"]);
    exit;
}

// Check if user is admin
if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Access denied. Admin privileges required."]);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

try {
    $userModel = new User();
    $deletedUsers = $userModel->getSoftDeletedUsers($limit, $offset);
    
    // Get total count for pagination
    $totalCountStmt = $userModel->getConnection()->prepare("SELECT COUNT(*) FROM users WHERE deleted_at IS NOT NULL");
    $totalCountStmt->execute();
    $totalCount = $totalCountStmt->fetchColumn();
    
    $totalPages = ceil($totalCount / $limit);
    
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "data" => $deletedUsers,
        "meta" => [
            "page" => $page,
            "limit" => $limit,
            "total" => $totalCount,
            "total_pages" => $totalPages,
            "has_next" => $page < $totalPages,
            "has_prev" => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching deleted accounts: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to fetch deleted accounts"]);
}
