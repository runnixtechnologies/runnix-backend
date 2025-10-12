<?php
// Test database connection only
header('Content-Type: application/json');

try {
    require_once '../config/Database.php';
    use Config\Database;
    
    $db = new Database();
    $conn = $db->getConnection();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
