<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\StoreController;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

try {
    $user = authenticateRequest();
    
    echo json_encode([
        'status' => 'debug',
        'user_data' => [
            'user_id' => $user['user_id'] ?? 'not_set',
            'role' => $user['role'] ?? 'not_set',
            'store_id' => $user['store_id'] ?? 'not_set'
        ],
        'message' => 'Authentication successful, checking store ID extraction...'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication failed: ' . $e->getMessage()
    ]);
}
