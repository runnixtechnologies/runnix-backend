<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Config\JwtHandler;
use function Middleware\authenticateRequest;

header('Content-Type: application/json');

// Test 1: Check if Authorization header exists
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? null;

if (!$authHeader) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Authorization header missing',
        'debug' => [
            'headers_received' => array_keys($headers),
            'authorization_header' => 'missing'
        ]
    ]);
    exit;
}

// Test 2: Check if token format is correct
if (!str_starts_with($authHeader, 'Bearer ')) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid Authorization header format. Should be: Bearer <token>',
        'debug' => [
            'authorization_header' => $authHeader
        ]
    ]);
    exit;
}

// Test 3: Extract and decode token
$token = str_replace('Bearer ', '', $authHeader);

try {
    $jwt = new JwtHandler();
    $decoded = $jwt->decode($token);
    
    if (!$decoded) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or expired token',
            'debug' => [
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 20) . '...'
            ]
        ]);
        exit;
    }
    
    // Test 4: Check token contents
    echo json_encode([
        'status' => 'success',
        'message' => 'Token decoded successfully',
        'debug' => [
            'token_contents' => $decoded,
            'user_id' => $decoded['user_id'] ?? 'not_set',
            'role' => $decoded['role'] ?? 'not_set',
            'store_id' => $decoded['store_id'] ?? 'not_set',
            'store_type_id' => $decoded['store_type_id'] ?? 'not_set'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Token decoding failed: ' . $e->getMessage(),
        'debug' => [
            'token_length' => strlen($token),
            'token_preview' => substr($token, 0, 20) . '...'
        ]
    ]);
}
?>
