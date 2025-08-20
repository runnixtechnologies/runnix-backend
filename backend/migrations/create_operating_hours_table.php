<?php
/**
 * Migration Script: Create operating hours table and update user profiles
 * Run this script to create the tables needed for operating hours and profile pictures
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating operating hours table and updating user profiles...\n";
    
    // Create store_operating_hours table
    $operatingHoursQuery = "CREATE TABLE IF NOT EXISTS store_operating_hours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        store_id INT NOT NULL,
        day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
        open_time TIME NULL,
        close_time TIME NULL,
        is_closed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        UNIQUE KEY unique_store_day (store_id, day_of_week)
    )";
    
    $stmt = $conn->prepare($operatingHoursQuery);
    $stmt->execute();
    echo "✅ Created store_operating_hours table\n";
    
    // Add profile_picture column to user_profiles table if it doesn't exist
    $checkColumnQuery = "SHOW COLUMNS FROM user_profiles LIKE 'profile_picture'";
    $checkStmt = $conn->prepare($checkColumnQuery);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        $addProfilePictureQuery = "ALTER TABLE user_profiles 
                                  ADD COLUMN profile_picture VARCHAR(255) NULL AFTER user_id";
        $stmt = $conn->prepare($addProfilePictureQuery);
        $stmt->execute();
        echo "✅ Added profile_picture column to user_profiles table\n";
    } else {
        echo "ℹ️  profile_picture column already exists in user_profiles table\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "You can now use operating hours and profile picture functionality.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
