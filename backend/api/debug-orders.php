<?php
// Simple debug endpoint to identify issues
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$debug = [];

// Add use statements at the top level
use Controller\OrderController;
use Model\User;
use function Middleware\authenticateRequest;

try {
    $debug[] = "✅ PHP is working";
    
    // Test 1: Check if we can include files
    try {
        require_once '../config/Database.php';
        $debug[] = "✅ Database.php included successfully";
    } catch (Exception $e) {
        $debug[] = "❌ Database.php failed: " . $e->getMessage();
    }
    
    // Test 2: Check database connection
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $debug[] = "✅ Database connection successful";
    } catch (Exception $e) {
        $debug[] = "❌ Database connection failed: " . $e->getMessage();
    }
    
    // Test 3: Check if tables exist
    try {
        $sql = "SHOW TABLES LIKE 'orders'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $ordersTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug[] = $ordersTable ? "✅ Orders table exists" : "❌ Orders table missing";
        
        $sql = "SHOW TABLES LIKE 'order_items'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $orderItemsTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug[] = $orderItemsTable ? "✅ Order_items table exists" : "❌ Order_items table missing";
        
        $sql = "SHOW TABLES LIKE 'users'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $usersTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug[] = $usersTable ? "✅ Users table exists" : "❌ Users table missing";
        
        $sql = "SHOW TABLES LIKE 'stores'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $storesTable = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug[] = $storesTable ? "✅ Stores table exists" : "❌ Stores table missing";
        
    } catch (Exception $e) {
        $debug[] = "❌ Table check failed: " . $e->getMessage();
    }
    
    // Test 4: Check if Order model exists
    try {
        require_once '../model/Order.php';
        $debug[] = "✅ Order.php included successfully";
        
        if (class_exists('Model\Order')) {
            $debug[] = "✅ Order class exists";
        } else {
            $debug[] = "❌ Order class not found";
        }
    } catch (Exception $e) {
        $debug[] = "❌ Order.php failed: " . $e->getMessage();
    }
    
    // Test 4.5: Check if User model exists
    try {
        require_once '../model/User.php';
        $debug[] = "✅ User.php included successfully";
        
        if (class_exists('Model\User')) {
            $debug[] = "✅ User class exists";
        } else {
            $debug[] = "❌ User class not found";
        }
    } catch (Exception $e) {
        $debug[] = "❌ User.php failed: " . $e->getMessage();
    }
    
    // Test 5: Check if OrderController exists
    try {
        require_once '../controller/OrderController.php';
        $debug[] = "✅ OrderController.php included successfully";
        
        if (class_exists('Controller\OrderController')) {
            $debug[] = "✅ OrderController class exists";
        } else {
            $debug[] = "❌ OrderController class not found";
        }
    } catch (Exception $e) {
        $debug[] = "❌ OrderController.php failed: " . $e->getMessage();
    }
    
    // Test 6: Check authentication middleware
    try {
        require_once '../middleware/authMiddleware.php';
        $debug[] = "✅ authMiddleware.php included successfully";
        
        if (function_exists('Middleware\authenticateRequest')) {
            $debug[] = "✅ authenticateRequest function exists";
        } else {
            $debug[] = "❌ authenticateRequest function not found";
        }
    } catch (Exception $e) {
        $debug[] = "❌ authMiddleware.php failed: " . $e->getMessage();
    }
    
    // Test 7: Check if we can instantiate User model
    try {
        $userModel = new User();
        $debug[] = "✅ User model instantiated successfully";
    } catch (Exception $e) {
        $debug[] = "❌ User model instantiation failed: " . $e->getMessage();
    }
    
    // Test 8: Check if we can instantiate OrderController
    try {
        $orderController = new OrderController();
        $debug[] = "✅ OrderController instantiated successfully";
    } catch (Exception $e) {
        $debug[] = "❌ OrderController instantiation failed: " . $e->getMessage();
    }
    
    // Test 9: Check PHP error reporting
    $debug[] = "PHP Error Reporting: " . error_reporting();
    $debug[] = "PHP Display Errors: " . ini_get('display_errors');
    $debug[] = "PHP Log Errors: " . ini_get('log_errors');
    $debug[] = "PHP Error Log: " . ini_get('error_log');
    
    // Test 9: Check file permissions
    $logFile = __DIR__ . '/php-error.log';
    $debug[] = "Log file path: " . $logFile;
    $debug[] = "Log file exists: " . (file_exists($logFile) ? "Yes" : "No");
    $debug[] = "Log file writable: " . (is_writable(dirname($logFile)) ? "Yes" : "No");
    
    // Test 10: Try to write to log file
    try {
        $testMessage = "[" . date('Y-m-d H:i:s') . "] Debug test message\n";
        file_put_contents($logFile, $testMessage, FILE_APPEND | LOCK_EX);
        $debug[] = "✅ Successfully wrote to log file";
    } catch (Exception $e) {
        $debug[] = "❌ Failed to write to log file: " . $e->getMessage();
    }
    
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Debug completed",
        "debug_info" => $debug,
        "php_version" => PHP_VERSION,
        "server_info" => [
            "REQUEST_METHOD" => $_SERVER['REQUEST_METHOD'],
            "REQUEST_URI" => $_SERVER['REQUEST_URI'],
            "HTTP_HOST" => $_SERVER['HTTP_HOST']
        ]
    ]);
    
} catch (Exception $e) {
    // This should never happen, but just in case
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Debug failed: " . $e->getMessage(),
        "debug_info" => $debug
    ]);
} catch (Error $e) {
    // Catch fatal errors
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Fatal error: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine(),
        "debug_info" => $debug
    ]);
}
?>
