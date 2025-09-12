<?php
// Detailed database connection test
header('Content-Type: application/json');

try {
    require_once '../config/Database.php';
    use Config\Database;
    
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn === null) {
        throw new Exception("Database connection returned null");
    }
    
    // Test a simple query
    $stmt = $conn->prepare("SELECT 1 as test");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection and query successful',
        'test_result' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database test failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
