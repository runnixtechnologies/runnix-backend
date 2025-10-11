<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if table exists
    $checkTable = "SHOW TABLES LIKE 'food_section_items'";
    $stmt = $conn->prepare($checkTable);
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Create the table
        $createTable = "
        CREATE TABLE IF NOT EXISTS food_section_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE,
            UNIQUE KEY unique_section_item_name (section_id, name)
        )";
        
        $conn->exec($createTable);
        echo json_encode([
            'status' => 'success',
            'message' => 'Table food_section_items created successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Table food_section_items already exists'
        ]);
    }
    
    // Test a simple query
    $testQuery = "SELECT COUNT(*) as count FROM food_section_items";
    $stmt = $conn->prepare($testQuery);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Table is working correctly',
        'item_count' => $result['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
