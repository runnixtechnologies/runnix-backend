<?php
// Test authentication middleware
// This is for development/testing purposes only

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Test if middleware file exists and can be loaded
    $middlewarePath = '../middleware/authMiddleware.php';
    
    if (!file_exists($middlewarePath)) {
        throw new Exception("Middleware file not found at: " . $middlewarePath);
    }
    
    require_once $middlewarePath;
    
    // Test if the function exists
    if (!function_exists('Middleware\authenticateRequest')) {
        throw new Exception("authenticateRequest function not found");
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Authentication middleware loaded successfully',
        'middleware_path' => $middlewarePath,
        'file_exists' => file_exists($middlewarePath),
        'function_exists' => function_exists('Middleware\authenticateRequest')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Middleware test failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
