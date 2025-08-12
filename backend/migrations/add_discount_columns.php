<?php
/**
 * Migration Script: Add discount and percentage columns to food_sides and packages tables
 * Run this script to add the new discount functionality
 */

require_once '../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Adding discount columns...\n";
    
    // Add columns to food_sides table
    $foodSidesQuery = "ALTER TABLE food_sides 
                       ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00,
                       ADD COLUMN percentage DECIMAL(5,2) DEFAULT 0.00";
    
    $stmt = $conn->prepare($foodSidesQuery);
    $stmt->execute();
    echo "✅ Added discount and percentage columns to food_sides table\n";
    
    // Add columns to packages table
    $packagesQuery = "ALTER TABLE packages 
                      ADD COLUMN discount DECIMAL(10,2) DEFAULT 0.00,
                      ADD COLUMN percentage DECIMAL(5,2) DEFAULT 0.00";
    
    $stmt = $conn->prepare($packagesQuery);
    $stmt->execute();
    echo "✅ Added discount and percentage columns to packages table\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "You can now use discount and percentage fields for food sides and packs.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
