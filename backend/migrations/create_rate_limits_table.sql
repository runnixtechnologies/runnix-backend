-- Create rate_limits table for OTP rate limiting
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'Phone number, email, or IP address',
    identifier_type ENUM('phone', 'email', 'ip') NOT NULL COMMENT 'Type of identifier',
    action VARCHAR(100) NOT NULL COMMENT 'Action being rate limited (e.g., send_otp)',
    request_count INT DEFAULT 1 COMMENT 'Number of requests made',
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Start of current time window',
    window_duration INT DEFAULT 3600 COMMENT 'Window duration in seconds (default 1 hour)',
    blocked_until TIMESTAMP NULL COMMENT 'When the identifier will be unblocked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_identifier_action (identifier, action),
    INDEX idx_window_start (window_start),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_identifier_type (identifier_type),
    
    -- Unique constraint to prevent duplicate entries
    UNIQUE KEY unique_identifier_action_window (identifier, action, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
