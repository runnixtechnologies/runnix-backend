<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit;
}

// Include necessary files
require_once '../config/Database.php';
require_once '../middleware/authMiddleware.php';

header('Content-Type: application/json');

use function Middleware\authenticateRequest;

// Authenticate user
$user = authenticateRequest();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $userId = $user['user_id'];
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $unread_only = $_GET['unread_only'] ?? false;
        
        // Validate pagination parameters
        $page = max(1, (int)$page);
        $limit = min(50, max(1, (int)$limit));
        $offset = ($page - 1) * $limit;
        
        $db = new Database();
        $conn = $db->getConnection();
        
        // Build query
        $sql = "SELECT on.*, o.order_number, o.status as order_status
                FROM order_notifications on
                LEFT JOIN orders o ON on.order_id = o.id
                WHERE on.user_id = :user_id";
        
        if ($unread_only) {
            $sql .= " AND on.is_read = 0";
        }
        
        $sql .= " ORDER BY on.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM order_notifications WHERE user_id = :user_id";
        if ($unread_only) {
            $countSql .= " AND is_read = 0";
        }
        
        $countStmt = $conn->prepare($countSql);
        $countStmt->bindParam(':user_id', $userId);
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $totalPages = ceil($totalCount / $limit);
        
        // Format notifications
        $formattedNotifications = [];
        foreach ($notifications as $notification) {
            $formattedNotifications[] = [
                'id' => $notification['id'],
                'order_id' => $notification['order_id'],
                'order_number' => $notification['order_number'],
                'order_status' => $notification['order_status'],
                'notification_type' => $notification['notification_type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'is_read' => (bool)$notification['is_read'],
                'created_at' => $notification['created_at'],
                'read_at' => $notification['read_at']
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data' => $formattedNotifications,
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
        error_log('Get order notifications error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving notifications."
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['notification_ids'])) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Notification IDs are required."
            ]);
            exit;
        }
        
        $userId = $user['user_id'];
        $notificationIds = $data['notification_ids'];
        
        // Validate notification IDs
        if (!is_array($notificationIds)) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Notification IDs must be an array."
            ]);
            exit;
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        // Mark notifications as read
        $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
        $sql = "UPDATE order_notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id IN ($placeholders) AND user_id = ?";
        
        $params = array_merge($notificationIds, [$userId]);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Notifications marked as read',
            'updated_count' => $stmt->rowCount()
        ]);
        
    } catch (Exception $e) {
        error_log('Mark notifications as read error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while updating notifications."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method not allowed. Only GET and POST requests are supported."
    ]);
}
?>
