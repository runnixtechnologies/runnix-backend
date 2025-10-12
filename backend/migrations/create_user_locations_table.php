<?php
/**
 * Migration: Create user_locations table
 * Purpose: Store user GPS locations for distance calculations and delivery
 */

require_once '../config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Create user_locations table
    $sql = "
    CREATE TABLE IF NOT EXISTS user_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        address TEXT,
        city VARCHAR(100),
        state VARCHAR(100),
        country VARCHAR(100) DEFAULT 'Nigeria',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_coordinates (latitude, longitude)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($sql);
    echo "✅ user_locations table created successfully!\n";
    
    // Add some sample data for testing (optional)
    $sampleData = "
    INSERT IGNORE INTO user_locations (user_id, latitude, longitude, address, city, state) VALUES
    (1, 6.5244, 3.3792, '64 Mubi Road, Mubi', 'Mubi', 'Adamawa'),
    (2, 6.4474, 3.3903, 'Victoria Island, Lagos', 'Lagos', 'Lagos'),
    (3, 9.0765, 7.3986, 'Central Business District, Abuja', 'Abuja', 'FCT');
    ";
    
    $conn->exec($sampleData);
    echo "✅ Sample location data inserted!\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating user_locations table: " . $e->getMessage() . "\n";
    error_log("Migration error: " . $e->getMessage());
}
