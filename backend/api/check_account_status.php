<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

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

try {
    $userModel = new User();
    $isDeleted = $userModel->isUserSoftDeleted($user['user_id']);
    
    if ($isDeleted) {
        // Get deletion details
        $stmt = $userModel->getConnection()->prepare("
            SELECT deleted_at, deletion_reason, reactivation_deadline, can_reactivate 
            FROM users 
            WHERE id = :user_id AND deleted_at IS NOT NULL
        ");
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();
        $deletionInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "account_status" => "deleted",
            "deleted_at" => $deletionInfo['deleted_at'],
            "deletion_reason" => $deletionInfo['deletion_reason'],
            "reactivation_deadline" => $deletionInfo['reactivation_deadline'],
            "can_reactivate" => (bool)$deletionInfo['can_reactivate'],
            "message" => "Your account has been deleted. Contact support to reactivate."
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "account_status" => "active",
            "message" => "Your account is active"
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error checking account status: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to check account status"]);
}
