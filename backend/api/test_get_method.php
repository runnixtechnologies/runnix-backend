<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../vendor/autoload.php';
require_once '../config/cors.php';

header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'GET method test working',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'get_data' => $_GET,
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
    ]
]);
