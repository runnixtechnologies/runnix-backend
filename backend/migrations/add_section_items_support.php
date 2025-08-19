<?php
require_once '../config/Database.php';

try {
    $conn = (new Config\Database())->getConnection();
    
    // Create food_item_section_items table for selective section item linking
    $foodItemSectionItemsQuery = "CREATE TABLE IF NOT EXISTS food_item_section_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        section_item_id INT NOT NULL,
        extra_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE,
        FOREIGN KEY (section_item_id) REFERENCES food_section_items(id) ON DELETE CASCADE,
        UNIQUE KEY unique_item_section_item (item_id, section_item_id)
    )";
    $stmt = $conn->prepare($foodItemSectionItemsQuery);
    $stmt->execute();
    echo "✅ Created food_item_section_items table\n";
    
    // Create food_item_section_items_config table for configuration
    $foodItemSectionItemsConfigQuery = "CREATE TABLE IF NOT EXISTS food_item_section_items_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        required BOOLEAN DEFAULT FALSE,
        max_quantity INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE
    )";
    $stmt = $conn->prepare($foodItemSectionItemsConfigQuery);
    $stmt->execute();
    echo "✅ Created food_item_section_items_config table\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "You can now use selective section item choice when creating food items.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
