<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';
require_once '../config/JwtHandler.php';

use function Middleware\authenticateRequest;
use Config\JwtHandler;

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

// Blacklist the token for immediate logout
$jwt = new JwtHandler();
$blacklisted = $jwt->blacklistToken($token);

if ($blacklisted) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success', 
        'message' => 'Logged out successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to logout. Please try again.'
    ]);
}
