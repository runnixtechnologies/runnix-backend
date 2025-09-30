<?php
// Migration: Create user_devices table
require_once '../config/database.php';

try {
    $pdo = (new Database())->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS user_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('customer', 'merchant', 'rider') NOT NULL,
        device_id VARCHAR(255) NOT NULL,
        device_type ENUM('ios', 'android', 'web') NOT NULL,
        device_model VARCHAR(255),
        os_version VARCHAR(50),
        app_version VARCHAR(20),
        screen_resolution VARCHAR(20),
        network_type VARCHAR(20),
        carrier_name VARCHAR(100),
        timezone VARCHAR(50),
        language VARCHAR(10),
        locale VARCHAR(10),
        is_active BOOLEAN DEFAULT TRUE,
        last_active_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_user_id (user_id),
        INDEX idx_device_id (device_id),
        INDEX idx_user_type (user_type),
        INDEX idx_is_active (is_active),
        INDEX idx_last_active (last_active_at),
        
        UNIQUE KEY unique_user_device (user_id, device_id),
        
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✅ user_devices table created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating user_devices table: " . $e->getMessage() . "\n";
}
?>
