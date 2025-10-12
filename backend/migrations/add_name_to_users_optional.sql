-- Optional: Add name fields to users table for better display
-- This is recommended but not required - the code works without it

-- Add first_name and last_name columns
ALTER TABLE users 
ADD COLUMN first_name VARCHAR(100) NULL AFTER email,
ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name;

-- Add index for faster name searches
ALTER TABLE users
ADD INDEX idx_full_name (first_name, last_name);

-- Optional: Add full_name virtual column (MySQL 5.7+)
-- ALTER TABLE users 
-- ADD COLUMN full_name VARCHAR(201) AS (CONCAT_WS(' ', first_name, last_name)) STORED;

