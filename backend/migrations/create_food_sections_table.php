<?php
/**
 * Migration Script: Create food_sections table
 * This table stores food sections that merchants can create for their stores
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating food_sections table...\n";
    
    // Create food_sections table
    $foodSectionsQuery = "CREATE TABLE IF NOT EXISTS food_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        section_name VARCHAR(255) NOT NULL,
        max_quantity INT DEFAULT 0,
        required BOOLEAN DEFAULT FALSE,
        price DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        INDEX idx_store_id (store_id),
        INDEX idx_status (status)
    )";
    
    $stmt = $conn->prepare($foodSectionsQuery);
    $stmt->execute();
    echo "✅ Created food_sections table\n";
    
    // Create food_section_items table for items within sections
    $foodSectionItemsQuery = "CREATE TABLE IF NOT EXISTS food_section_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        item_id INT NOT NULL,
        extra_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE,
        UNIQUE KEY unique_section_item (section_id, item_id),
        INDEX idx_section_id (section_id),
        INDEX idx_item_id (item_id)
    )";
    
    $stmt = $conn->prepare($foodSectionItemsQuery);
    $stmt->execute();
    echo "✅ Created food_section_items table\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "The food_sections table is now ready for use.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
?>
