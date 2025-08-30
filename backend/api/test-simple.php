<?php
/**
 * Simple test endpoint
 */

header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Simple test endpoint working',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);
