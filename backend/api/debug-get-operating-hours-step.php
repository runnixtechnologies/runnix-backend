<?php
/**
 * Step-by-step debug version of get-operating-hours
 * This will help identify exactly where the issue occurs
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo json_encode([
    'step' => 1,
    'message' => 'Starting debug process',
    'timestamp' => date('Y-m-d H:i:s')
]);
exit;

try {
    echo json_encode([
        'step' => 2,
        'message' => 'Loading autoload.php'
    ]);
    exit;
    
    require_once '../../vendor/autoload.php';
    
    echo json_encode([
        'step' => 3,
        'message' => 'Loading cors.php'
    ]);
    exit;
    
    require_once '../config/cors.php';
    
    echo json_encode([
        'step' => 4,
        'message' => 'Loading authMiddleware.php'
    ]);
    exit;
    
    require_once '../middleware/authMiddleware.php';
    
    echo json_encode([
        'step' => 5,
        'message' => 'Setting up imports'
    ]);
    exit;
    
    // Note: use statements must be at the top level, not inside try-catch
    // This is just for debugging purposes
    
    echo json_encode([
        'step' => 6,
        'message' => 'Setting content type header'
    ]);
    exit;
    
    header('Content-Type: application/json');
    
    echo json_encode([
        'step' => 7,
        'message' => 'Authenticating request'
    ]);
    exit;
    
    $user = authenticateRequest();
    
    echo json_encode([
        'step' => 8,
        'message' => 'Checking user role',
        'user_role' => $user['role'] ?? 'not_set'
    ]);
    exit;
    
    // Check if user is a merchant
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Only merchants can access operating hours.',
            'user_role' => $user['role'] ?? 'not_set'
        ]);
        exit;
    }
    
    echo json_encode([
        'step' => 9,
        'message' => 'Creating StoreController'
    ]);
    exit;
    
    $controller = new StoreController();
    
    echo json_encode([
        'step' => 10,
        'message' => 'Calling getOperatingHours'
    ]);
    exit;
    
    $response = $controller->getOperatingHours($user);
    
    echo json_encode([
        'step' => 11,
        'message' => 'Returning response',
        'response' => $response
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception occurred: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
