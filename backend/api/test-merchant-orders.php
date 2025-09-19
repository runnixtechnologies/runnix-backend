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

header('Content-Type: application/json');

try {
    // Test authentication
    require_once '../middleware/authMiddleware.php';
    use function Middleware\authenticateRequest;
    
    $user = authenticateRequest();
    
    // Check if user is a merchant
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode([
            "status" => "error",
            "message" => "Access denied. Only store owners can view orders."
        ]);
        exit;
    }
    
    // Test database connection and get merchant's stores
    require_once '../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get merchant's stores
    $sql = "SELECT id, store_name, user_id FROM stores WHERE user_id = :merchant_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':merchant_id', $user['user_id']);
    $stmt->execute();
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get orders count for each store
    $storeOrders = [];
    foreach ($stores as $store) {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE store_id = :store_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':store_id', $store['id']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $storeOrders[] = [
            'store_id' => $store['id'],
            'store_name' => $store['store_name'],
            'orders_count' => $result['count'] ?? 0
        ];
    }
    
    // Get total orders for merchant
    $sql = "SELECT COUNT(*) as count FROM orders WHERE merchant_id = :merchant_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':merchant_id', $user['user_id']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalOrders = $result['count'] ?? 0;
    
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Merchant orders test successful",
        "data" => [
            "merchant_id" => $user['user_id'],
            "merchant_name" => $user['first_name'] . ' ' . $user['last_name'],
            "total_orders" => $totalOrders,
            "stores" => $storeOrders,
            "orders_by_status" => [
                "pending" => 0,
                "accepted" => 0,
                "preparing" => 0,
                "ready_for_pickup" => 0,
                "in_transit" => 0,
                "delivered" => 0,
                "cancelled" => 0
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Test merchant orders error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Test failed: " . $e->getMessage()
    ]);
}
?>
