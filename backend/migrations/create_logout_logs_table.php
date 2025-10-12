<?php
/**
 * Migration Script: Create Logout Logs Table
 * This table tracks logout events for analytics and security monitoring
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating logout_logs table...\n";
    
    // Create logout_logs table
    $createLogoutLogsTable = "
    CREATE TABLE IF NOT EXISTS logout_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_role ENUM('user', 'merchant', 'rider') NOT NULL,
        logout_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        logout_type ENUM('manual', 'auto', 'inactivity', 'security') DEFAULT 'manual',
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_info JSON NULL,
        session_duration_minutes DECIMAL(10, 2),
        token_blacklisted BOOLEAN DEFAULT TRUE,
        session_deactivated BOOLEAN DEFAULT TRUE,
        logout_reason VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_logouts (user_id, logout_time),
        INDEX idx_role_logouts (user_role, logout_time),
        INDEX idx_logout_type (logout_type, logout_time),
        INDEX idx_logout_time (logout_time)
    )";
    
    $conn->exec($createLogoutLogsTable);
    echo "✅ Created logout_logs table\n";
    
    // Create cleanup event for old logout logs (keep for 90 days)
    $createCleanupEvent = "
    CREATE EVENT IF NOT EXISTS cleanup_old_logout_logs
    ON SCHEDULE EVERY 1 DAY
    DO
        DELETE FROM logout_logs 
        WHERE logout_time < DATE_SUB(NOW(), INTERVAL 90 DAY)";
    
    try {
        $conn->exec($createCleanupEvent);
        echo "✅ Created cleanup event for old logout logs\n";
    } catch (Exception $e) {
        echo "⚠️  Cleanup event creation failed (may need EVENT privileges): " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Logout logs table migration completed successfully!\n";
    echo "This enables logout tracking and analytics.\n";
    echo "Logout logs older than 90 days will be automatically cleaned up.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
