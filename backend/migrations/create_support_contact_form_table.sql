-- Migration: Create support_contact_form table
-- This table stores support requests with pin verification

CREATE TABLE IF NOT EXISTS support_contact_form (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    interest_complaints TEXT NOT NULL,
    message TEXT NOT NULL,
    support_pin VARCHAR(8) NOT NULL,
    user_id INT NOT NULL,
    is_verified BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    admin_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_support_pin (support_pin),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Add comment to table
ALTER TABLE support_contact_form COMMENT = 'Support contact form submissions with pin verification';
