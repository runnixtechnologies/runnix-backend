<?php
/**
 * Migration Script: Fix sections configuration tables for new format
 * This adds the missing section_id columns needed for the new sections format
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Fixing sections configuration tables...\n";
    
    // Add section_id column to food_item_sections_config table
    $alterSectionsConfigQuery = "ALTER TABLE food_item_sections_config 
                                 ADD COLUMN section_id INT NULL AFTER item_id,
                                 ADD FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE";
    
    try {
        $stmt = $conn->prepare($alterSectionsConfigQuery);
        $stmt->execute();
        echo "✅ Added section_id column to food_item_sections_config table\n";
    } catch (PDOException $e) {
        if ($e->getCode() == 1060) { // Duplicate column name
            echo "ℹ️  section_id column already exists in food_item_sections_config table\n";
        } else {
            throw $e;
        }
    }
    
    // Add section_id column to food_item_section_items_config table
    $alterSectionItemsConfigQuery = "ALTER TABLE food_item_section_items_config 
                                    ADD COLUMN section_id INT NULL AFTER item_id,
                                    ADD FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE";
    
    try {
        $stmt = $conn->prepare($alterSectionItemsConfigQuery);
        $stmt->execute();
        echo "✅ Added section_id column to food_item_section_items_config table\n";
    } catch (PDOException $e) {
        if ($e->getCode() == 1060) { // Duplicate column name
            echo "ℹ️  section_id column already exists in food_item_section_items_config table\n";
        } else {
            throw $e;
        }
    }
    
    // Add section_id column to food_item_section_items table
    $alterSectionItemsQuery = "ALTER TABLE food_item_section_items 
                              ADD COLUMN section_id INT NULL AFTER item_id,
                              ADD FOREIGN KEY (section_id) REFERENCES food_sections(id) ON DELETE CASCADE";
    
    try {
        $stmt = $conn->prepare($alterSectionItemsQuery);
        $stmt->execute();
        echo "✅ Added section_id column to food_item_section_items table\n";
    } catch (PDOException $e) {
        if ($e->getCode() == 1060) { // Duplicate column name
            echo "ℹ️  section_id column already exists in food_item_section_items table\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "The new sections format should now work properly.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}


