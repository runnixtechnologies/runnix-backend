<?php
/**
 * Migration Script: Create food item configuration tables
 * Run this script to create the tables needed for enhanced food item creation with sides, packs, and sections
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating food item configuration tables...\n";
    
    // Create food_item_sides_config table
    $sidesConfigQuery = "CREATE TABLE IF NOT EXISTS food_item_sides_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        required BOOLEAN DEFAULT FALSE,
        max_quantity INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE
    )";
    
    $stmt = $conn->prepare($sidesConfigQuery);
    $stmt->execute();
    echo "✅ Created food_item_sides_config table\n";
    
    // Create food_item_packs_config table
    $packsConfigQuery = "CREATE TABLE IF NOT EXISTS food_item_packs_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        required BOOLEAN DEFAULT FALSE,
        max_quantity INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE
    )";
    
    $stmt = $conn->prepare($packsConfigQuery);
    $stmt->execute();
    echo "✅ Created food_item_packs_config table\n";
    
    // Create food_item_sections_config table
    $sectionsConfigQuery = "CREATE TABLE IF NOT EXISTS food_item_sections_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        required BOOLEAN DEFAULT FALSE,
        max_quantity INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE
    )";
    
    $stmt = $conn->prepare($sectionsConfigQuery);
    $stmt->execute();
    echo "✅ Created food_item_sections_config table\n";
    
    // Create food_item_sides table (if it doesn't exist)
    $foodItemSidesQuery = "CREATE TABLE IF NOT EXISTS food_item_sides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        side_id INT NOT NULL,
        extra_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE,
        FOREIGN KEY (side_id) REFERENCES food_sides(id) ON DELETE CASCADE,
        UNIQUE KEY unique_item_side (item_id, side_id)
    )";
    
    $stmt = $conn->prepare($foodItemSidesQuery);
    $stmt->execute();
    echo "✅ Created food_item_sides table\n";
    
    // Create food_item_packs table (if it doesn't exist)
    $foodItemPacksQuery = "CREATE TABLE IF NOT EXISTS food_item_packs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        pack_id INT NOT NULL,
        extra_price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE,
        FOREIGN KEY (pack_id) REFERENCES packages(id) ON DELETE CASCADE,
        UNIQUE KEY unique_item_pack (item_id, pack_id)
    )";
    
    $stmt = $conn->prepare($foodItemPacksQuery);
    $stmt->execute();
    echo "✅ Created food_item_packs table\n";
    
    // Create food_item_sections table (if it doesn't exist)
    $foodItemSectionsQuery = "CREATE TABLE IF NOT EXISTS food_item_sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        section_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES food_items(id) ON DELETE CASCADE,
        FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE,
        UNIQUE KEY unique_item_section (item_id, section_id)
    )";
    
    $stmt = $conn->prepare($foodItemSectionsQuery);
    $stmt->execute();
    echo "✅ Created food_item_sections table\n";
    
    // Create food_section_sides table (if it doesn't exist)
    $foodSectionSidesQuery = "CREATE TABLE IF NOT EXISTS food_section_sides (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_id INT NOT NULL,
        side_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE,
        FOREIGN KEY (side_id) REFERENCES food_sides(id) ON DELETE CASCADE,
        UNIQUE KEY unique_section_side (section_id, side_id)
    )";
    
    $stmt = $conn->prepare($foodSectionSidesQuery);
    $stmt->execute();
    echo "✅ Created food_section_sides table\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "You can now use the enhanced create food item functionality with sides, packs, and sections.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
