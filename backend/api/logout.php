<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use function Middleware\authenticateRequest;

header('Content-Type: application/json');

$user = authenticateRequest();

// Get the authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Authorization header is required']);
    exit;
}

$token = $matches[1];

// Invalidate the token (you might want to store invalidated tokens in a blacklist table)
// For now, we'll just return success since JWT tokens are stateless

http_response_code(200);
echo json_encode([
    'status' => 'success', 
    'message' => 'Logged out successfully'
]);
