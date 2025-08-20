<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/Database.php';
require_once '../controller/FoodItemController.php';
require_once '../middleware/authMiddleware.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Step 1: Test database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    // Step 2: Test if we can query the food_sections table
    $testQuery = "SELECT id, section_name FROM food_sections WHERE id = 11 LIMIT 1";
    $stmt = $conn->prepare($testQuery);
    $stmt->execute();
    $section = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$section) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Section with ID 11 not found'
        ]);
        exit;
    }
    
    // Step 3: Test if we can query food_section_items table
    $testItemsQuery = "SELECT COUNT(*) as count FROM food_section_items WHERE section_id = 11";
    $stmt = $conn->prepare($testItemsQuery);
    $stmt->execute();
    $itemCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Step 4: Test authentication
    $user = authenticateRequest();
    if (!$user) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication failed'
        ]);
        exit;
    }
    
    // Step 5: Test controller instantiation
    $controller = new FoodItemController();
    
    // Step 6: Test the actual creation with hardcoded data
    $testData = [
        'section_id' => 11,
        'name' => 'Test Item Debug',
        'price' => 1.00
    ];
    
    $response = $controller->createSectionItem($testData, $user);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'All tests passed',
        'section_found' => $section,
        'item_count' => $itemCount['count'],
        'user_role' => $user['role'],
        'user_store_id' => $user['store_id'],
        'response' => $response
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
