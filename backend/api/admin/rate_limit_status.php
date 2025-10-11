<?php
// Admin endpoint to check rate limit status
// Requires admin authentication

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../../vendor/autoload.php';
require_once '../../config/cors.php';
require_once '../../middleware/authMiddleware.php';
require_once '../../middleware/rateLimitMiddleware.php';

use function Middleware\authenticateRequest;
use function Middleware\getRateLimitStatus;
use Controller\RateLimiterController;

header('Content-Type: application/json');

// Authenticate admin user
$user = authenticateRequest();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin access required'
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $data['identifier'] ?? null;
    $identifierType = $data['identifier_type'] ?? 'phone';
    $purpose = $data['purpose'] ?? null;
    
    if (!$identifier) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Identifier is required'
        ]);
        exit;
    }
    
    try {
        $status = getRateLimitStatus($identifier, $identifierType, $purpose);
        
        echo json_encode([
            'status' => 'success',
            'identifier' => $identifier,
            'identifier_type' => $identifierType,
            'purpose' => $purpose,
            'rate_limit_status' => $status
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to get rate limit status: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'status' => 'info',
        'message' => 'Rate limit status endpoint',
        'usage' => 'Send POST request with identifier, identifier_type, and purpose',
        'example' => [
            'identifier' => '2348123456789',
            'identifier_type' => 'phone',
            'purpose' => 'signup'
        ],
        'identifier_types' => ['phone', 'email', 'ip'],
        'purposes' => ['signup', 'password_reset', 'login', 'verification']
    ], JSON_PRETTY_PRINT);
}
