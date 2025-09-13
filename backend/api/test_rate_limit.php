<?php
// Test rate limiting functionality
// This is for development/testing purposes only

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/rateLimitMiddleware.php';

use Controller\RateLimiterController;
use function Middleware\checkOtpRateLimit;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $data['phone'] ?? '2348123456789';
    $email = $data['email'] ?? 'test@example.com';
    $purpose = $data['purpose'] ?? 'signup';
    
    // Format phone if needed
    if (preg_match('/^0?\d{10}$/', $phone)) {
        $phone = '234' . ltrim($phone, '0');
    }
    
    try {
        // Test rate limiting
        $rateLimitResult = checkOtpRateLimit($phone, $email, $purpose);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Rate limit test completed',
            'test_data' => [
                'phone' => $phone,
                'email' => $email,
                'purpose' => $purpose
            ],
            'rate_limit_result' => $rateLimitResult
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Rate limit test failed: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_PRETTY_PRINT);
    }
    
} else {
    echo json_encode([
        'status' => 'info',
        'message' => 'Rate limit test endpoint',
        'usage' => 'Send POST request with phone, email, and purpose',
        'example' => [
            'phone' => '08123456789',
            'email' => 'test@example.com',
            'purpose' => 'signup'
        ],
        'rate_limits' => [
            'signup' => '3 requests per hour',
            'password_reset' => '5 requests per hour',
            'login' => '10 requests per hour',
            'verification' => '3 requests per hour',
            'default' => '5 requests per hour'
        ]
    ], JSON_PRETTY_PRINT);
}
