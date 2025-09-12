<?php
// Test model loading only
header('Content-Type: application/json');

try {
    require_once '../config/Database.php';
    require_once '../model/Store.php';
    
    use Config\Database;
    use Model\Store;
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $storeModel = new Store();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Models loaded successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Model loading failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
