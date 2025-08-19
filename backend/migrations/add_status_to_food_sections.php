<?php
require_once '../config/Database.php';

try {
    $conn = (new Config\Database())->getConnection();
    
    // Add status column to food_sections table
    $alterQuery = "ALTER TABLE food_sections ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER required";
    $stmt = $conn->prepare($alterQuery);
    $stmt->execute();
    
    echo "✅ Successfully added status column to food_sections table\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
