<?php
/**
 * Migration Script: Create User Activity Table
 * This table tracks user activity for inactivity-based auto-logout
 */

require_once __DIR__ . '/../config/Database.php';

use Config\Database;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting migration: Creating user_activity table...\n";
    
    // Create user_activity table
    $createUserActivityTable = "
    CREATE TABLE IF NOT EXISTS user_activity (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        user_role ENUM('user', 'merchant', 'rider') NOT NULL,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        session_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        device_info JSON NULL,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_activity (user_id, last_activity),
        INDEX idx_role_activity (user_role, last_activity),
        INDEX idx_active_sessions (is_active, last_activity),
        UNIQUE KEY unique_active_session (user_id, is_active)
    )";
    
    $conn->exec($createUserActivityTable);
    echo "✅ Created user_activity table\n";
    
    // Create cleanup event for old activity records
    $createCleanupEvent = "
    CREATE EVENT IF NOT EXISTS cleanup_old_activity
    ON SCHEDULE EVERY 1 HOUR
    DO
        DELETE FROM user_activity 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND is_active = FALSE";
    
    try {
        $conn->exec($createCleanupEvent);
        echo "✅ Created cleanup event for old activity records\n";
    } catch (Exception $e) {
        echo "⚠️  Cleanup event creation failed (may need EVENT privileges): " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 User activity table migration completed successfully!\n";
    echo "This enables inactivity-based auto-logout functionality.\n";
    echo "Activity records older than 24 hours will be automatically cleaned up.\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
