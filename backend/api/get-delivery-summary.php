<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request for OPTIONS method (CORS preflight check)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
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
        $orderId = $_GET['order_id'] ?? null;
        
        if (!$orderId) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Order ID is required."
            ]);
            exit;
        }
        
        // Validate order ID
        if (!is_numeric($orderId) || $orderId <= 0) {
            http_response_code(400);
            echo json_encode([
                "status" => "error",
                "message" => "Invalid order ID."
            ]);
            exit;
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get order details
        $sql = "SELECT o.*, 
                       s.store_name,
                       c.first_name as customer_first_name,
                       c.last_name as customer_last_name,
                       c.phone as customer_phone,
                       r.first_name as rider_first_name,
                       r.last_name as rider_last_name,
                       r.phone as rider_phone
                FROM orders o
                LEFT JOIN stores s ON o.store_id = s.id
                LEFT JOIN user_profiles c ON o.customer_id = c.user_id
                LEFT JOIN user_profiles r ON o.rider_id = r.user_id
                WHERE o.id = :order_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            http_response_code(404);
            echo json_encode([
                "status" => "error",
                "message" => "Order not found."
            ]);
            exit;
        }
        
        // Check permissions
        $userId = $user['user_id'];
        if ($order['customer_id'] != $userId && $order['merchant_id'] != $userId && $order['rider_id'] != $userId) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Access denied."
            ]);
            exit;
        }
        
        // Get status history
        $sql = "SELECT osh.*, 
                       up.first_name, up.last_name
                FROM order_status_history osh
                LEFT JOIN user_profiles up ON osh.changed_by = up.user_id
                WHERE osh.order_id = :order_id
                ORDER BY osh.created_at ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $statusHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get delivery tracking
        $sql = "SELECT dt.*, 
                       up.first_name, up.last_name, up.phone
                FROM delivery_tracking dt
                LEFT JOIN user_profiles up ON dt.rider_id = up.user_id
                WHERE dt.order_id = :order_id
                ORDER BY dt.created_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':order_id', $orderId);
        $stmt->execute();
        $tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format delivery summary
        $deliverySummary = [
            'order_id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'store_name' => $order['store_name'],
            'delivery_address' => $order['delivery_address'],
            'delivery_instructions' => $order['delivery_instructions'],
            'customer' => [
                'name' => trim($order['customer_first_name'] . ' ' . $order['customer_last_name']),
                'phone' => $order['customer_phone']
            ],
            'rider' => $order['rider_id'] ? [
                'name' => trim($order['rider_first_name'] . ' ' . $order['rider_last_name']),
                'phone' => $order['rider_phone']
            ] : null,
            'timeline' => $this->formatDeliveryTimeline($order, $statusHistory),
            'tracking' => $tracking,
            'estimated_delivery' => $this->calculateEstimatedDelivery($order, $tracking),
            'created_at' => $order['created_at']
        ];
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'data' => $deliverySummary
        ]);
        
    } catch (Exception $e) {
        error_log('Get delivery summary error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "An error occurred while retrieving delivery summary."
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
 * Format delivery timeline
 */
function formatDeliveryTimeline($order, $statusHistory)
{
    $timeline = [];
    
    // Order placed
    $timeline[] = [
        'status' => 'Order Placed',
        'time' => $order['created_at'],
        'completed' => true,
        'description' => 'Order was placed successfully'
    ];
    
    // Order accepted
    if ($order['accepted_at']) {
        $timeline[] = [
            'status' => 'Order Accepted',
            'time' => $order['accepted_at'],
            'completed' => true,
            'description' => 'Merchant accepted the order'
        ];
    } elseif ($order['status'] === 'pending') {
        $timeline[] = [
            'status' => 'Order Accepted',
            'time' => null,
            'completed' => false,
            'description' => 'Waiting for merchant to accept'
        ];
    }
    
    // Order ready
    if ($order['ready_at']) {
        $timeline[] = [
            'status' => 'Ready for Pickup',
            'time' => $order['ready_at'],
            'completed' => true,
            'description' => 'Order is ready for pickup'
        ];
    } elseif (in_array($order['status'], ['accepted', 'preparing'])) {
        $timeline[] = [
            'status' => 'Ready for Pickup',
            'time' => null,
            'completed' => false,
            'description' => 'Order is being prepared'
        ];
    }
    
    // Order picked up
    if ($order['picked_up_at']) {
        $timeline[] = [
            'status' => 'Picked Up',
            'time' => $order['picked_up_at'],
            'completed' => true,
            'description' => 'Order has been picked up by rider'
        ];
    } elseif ($order['status'] === 'ready_for_pickup') {
        $timeline[] = [
            'status' => 'Picked Up',
            'time' => null,
            'completed' => false,
            'description' => 'Waiting for rider to pick up'
        ];
    }
    
    // Order delivered
    if ($order['delivered_at']) {
        $timeline[] = [
            'status' => 'Delivered',
            'time' => $order['delivered_at'],
            'completed' => true,
            'description' => 'Order has been delivered successfully'
        ];
    } elseif ($order['status'] === 'in_transit') {
        $timeline[] = [
            'status' => 'Delivered',
            'time' => null,
            'completed' => false,
            'description' => 'Order is on its way'
        ];
    }
    
    // Order cancelled
    if ($order['cancelled_at']) {
        $timeline[] = [
            'status' => 'Cancelled',
            'time' => $order['cancelled_at'],
            'completed' => true,
            'description' => 'Order has been cancelled'
        ];
    }
    
    return $timeline;
}

/**
 * Calculate estimated delivery time
 */
function calculateEstimatedDelivery($order, $tracking)
{
    if ($order['status'] === 'delivered') {
        return null; // Already delivered
    }
    
    if ($order['status'] === 'cancelled') {
        return null; // Cancelled
    }
    
    // Get latest tracking entry
    $latestTracking = !empty($tracking) ? $tracking[0] : null;
    
    if ($latestTracking && $latestTracking['estimated_delivery_time']) {
        return $latestTracking['estimated_delivery_time']; // minutes
    }
    
    // Default estimates based on status
    $estimates = [
        'pending' => 30, // 30 minutes to accept
        'accepted' => 25, // 25 minutes to prepare
        'preparing' => 20, // 20 minutes to ready
        'ready_for_pickup' => 15, // 15 minutes to pickup
        'in_transit' => 10 // 10 minutes to deliver
    ];
    
    return $estimates[$order['status']] ?? 30;
}
?>
