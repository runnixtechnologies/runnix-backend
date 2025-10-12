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
    // Test database connection
    require_once '../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Test if orders table exists
    $sql = "SHOW TABLES LIKE 'orders'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $ordersTableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test if order_items table exists
    $sql = "SHOW TABLES LIKE 'order_items'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $orderItemsTableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test if stores table exists
    $sql = "SHOW TABLES LIKE 'stores'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $storesTableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test if users table exists
    $sql = "SHOW TABLES LIKE 'users'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $usersTableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Count records in each table
    $ordersCount = 0;
    $orderItemsCount = 0;
    $storesCount = 0;
    $usersCount = 0;
    
    if ($ordersTableExists) {
        $sql = "SELECT COUNT(*) as count FROM orders";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $ordersCount = $result['count'];
    }
    
    if ($orderItemsTableExists) {
        $sql = "SELECT COUNT(*) as count FROM order_items";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $orderItemsCount = $result['count'];
    }
    
    if ($storesTableExists) {
        $sql = "SELECT COUNT(*) as count FROM stores";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $storesCount = $result['count'];
    }
    
    if ($usersTableExists) {
        $sql = "SELECT COUNT(*) as count FROM users";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $usersCount = $result['count'];
    }
    
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Database connection test successful",
        "data" => [
            "tables" => [
                "orders" => $ordersTableExists ? "exists" : "missing",
                "order_items" => $orderItemsTableExists ? "exists" : "missing",
                "stores" => $storesTableExists ? "exists" : "missing",
                "users" => $usersTableExists ? "exists" : "missing"
            ],
            "record_counts" => [
                "orders" => $ordersCount,
                "order_items" => $orderItemsCount,
                "stores" => $storesCount,
                "users" => $usersCount
            ]
        ]
    ]);
    
} catch (Exception $e) {
    $errorMessage = 'Database connection test error: ' . $e->getMessage() . ' | Stack trace: ' . $e->getTraceAsString();
    error_log($errorMessage, 3, __DIR__ . '/php-error.log');
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
}
?>
