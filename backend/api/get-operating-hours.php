<?php

// Error reporting disabled for production
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
    
    // Check if user is a merchant
    if ($user['role'] !== 'merchant') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Only merchants can access operating hours.'
        ]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage(),
        'debug' => 'Make sure to include Authorization header: Bearer YOUR_JWT_TOKEN'
    ]);
    exit;
}

$controller = new StoreController();
$response = $controller->getOperatingHours($user);

echo json_encode($response);
