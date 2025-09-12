-- Migration: Add soft delete fields to users table
-- This migration adds fields to support soft deletion of user accounts

ALTER TABLE users 
ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp when account was soft deleted',
ADD COLUMN deleted_by INT NULL DEFAULT NULL COMMENT 'User ID who initiated the deletion (self-deletion or admin)',
ADD COLUMN deletion_reason TEXT NULL DEFAULT NULL COMMENT 'Reason provided by user for account deletion',
ADD COLUMN deletion_method ENUM('self', 'admin', 'system') DEFAULT 'self' COMMENT 'Method used to delete the account',
ADD COLUMN can_reactivate BOOLEAN DEFAULT TRUE COMMENT 'Whether the account can be reactivated',
ADD COLUMN reactivation_deadline TIMESTAMP NULL DEFAULT NULL COMMENT 'Deadline for account reactivation (NULL = no deadline)';

-- Add index for soft delete queries
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
CREATE INDEX idx_users_deletion_method ON users(deletion_method);

-- Add foreign key constraint for deleted_by (self-reference)
ALTER TABLE users 
ADD CONSTRAINT fk_users_deleted_by 
FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL;
