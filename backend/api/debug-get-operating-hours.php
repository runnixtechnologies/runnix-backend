<?php
/**
 * Debug endpoint for get-operating-hours
 * This will help identify where the issue is occurring
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

header('Content-Type: application/json');

echo json_encode([
    'status' => 'debug',
    'message' => 'Debug endpoint loaded successfully',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'headers' => getallheaders()
    ]
]);
