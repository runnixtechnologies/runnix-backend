-- Add missing columns to user_devices table if they don't exist

-- Check if columns exist and add them
ALTER TABLE user_devices 
ADD COLUMN IF NOT EXISTS user_agent TEXT,
ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45);

-- Add index on device_id for faster lookups
ALTER TABLE user_devices 
ADD INDEX IF NOT EXISTS idx_device_id (device_id);

-- Add index on user_id for faster lookups
ALTER TABLE user_devices 
ADD INDEX IF NOT EXISTS idx_user_id (user_id);

