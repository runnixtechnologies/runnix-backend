-- Create user_devices table for automatic device tracking
CREATE TABLE IF NOT EXISTS user_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('customer', 'merchant', 'rider') NOT NULL,
    device_id VARCHAR(255) NOT NULL, -- Unique device identifier (UDID/Android ID)
    device_type ENUM('ios', 'android', 'web') NOT NULL,
    device_model VARCHAR(255), -- iPhone 14, Samsung Galaxy S23, etc.
    os_version VARCHAR(50), -- iOS 17.1, Android 14, etc.
    app_version VARCHAR(20), -- Your app version
    screen_resolution VARCHAR(20), -- 1080x1920, etc.
    network_type VARCHAR(20), -- wifi, 4g, 5g, etc.
    carrier_name VARCHAR(100), -- MTN, Airtel, etc.
    timezone VARCHAR(50), -- Africa/Lagos, etc.
    language VARCHAR(10), -- en, fr, etc.
    locale VARCHAR(10), -- en-US, fr-FR, etc.
    user_agent TEXT, -- For web requests
    ip_address VARCHAR(45), -- For web requests
    is_active BOOLEAN DEFAULT TRUE,
    last_active_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    first_seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_device_id (device_id),
    INDEX idx_user_type (user_type),
    INDEX idx_is_active (is_active),
    INDEX idx_last_active (last_active_at),
    
    -- Unique constraint: one device per user (or allow multiple devices per user)
    UNIQUE KEY unique_user_device (user_id, device_id),
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
