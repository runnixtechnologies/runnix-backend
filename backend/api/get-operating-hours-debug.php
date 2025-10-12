<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';
require_once '../middleware/authMiddleware.php';

use Controller\StoreController;
use Config\JwtHandler;

header('Content-Type: application/json');

// Debug: Check if token is in headers or query params
$headers = getallheaders();
$tokenFromHeader = $headers['Authorization'] ?? null;
$tokenFromQuery = $_GET['token'] ?? null;

echo json_encode([
    'debug' => [
        'headers_received' => array_keys($headers),
        'authorization_header' => $tokenFromHeader ? 'present' : 'missing',
        'token_from_query' => $tokenFromQuery ? 'present' : 'missing',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ]
]);
exit;

// If you want to test with query parameter, uncomment this:
/*
if ($tokenFromQuery) {
    $jwt = new JwtHandler();
    $decoded = $jwt->decode($tokenFromQuery);
    
    if ($decoded) {
        $user = $decoded;
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Invalid token from query']);
        exit;
    }
} else {
    $user = authenticateRequest();
}
*/
