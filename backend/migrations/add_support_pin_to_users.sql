-- Migration: Add support_pin column to users table
-- This adds a unique support pin for each user to authenticate support requests

ALTER TABLE users 
ADD COLUMN support_pin VARCHAR(8) UNIQUE NOT NULL AFTER referral_code;

-- Create index for faster lookups
CREATE INDEX idx_users_support_pin ON users(support_pin);

-- Update existing users with generated support pins
-- This will generate a unique 8-character support pin for each existing user
UPDATE users 
SET support_pin = CONCAT(
    CHAR(65 + FLOOR(RAND() * 26)),  -- Random letter A-Z
    CHAR(65 + FLOOR(RAND() * 26)),  -- Random letter A-Z
    CHAR(65 + FLOOR(RAND() * 26)),  -- Random letter A-Z
    CHAR(65 + FLOOR(RAND() * 26)),  -- Random letter A-Z
    CHAR(48 + FLOOR(RAND() * 10)),  -- Random digit 0-9
    CHAR(48 + FLOOR(RAND() * 10)),  -- Random digit 0-9
    CHAR(48 + FLOOR(RAND() * 10)),  -- Random digit 0-9
    CHAR(48 + FLOOR(RAND() * 10))   -- Random digit 0-9
)
WHERE support_pin IS NULL OR support_pin = '';

-- Ensure all support pins are unique by updating duplicates
-- This is a safety measure in case the random generation creates duplicates
UPDATE users u1 
SET support_pin = CONCAT(
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(65 + FLOOR(RAND() * 26)),
    CHAR(48 + FLOOR(RAND() * 10)),
    CHAR(48 + FLOOR(RAND() * 10)),
    CHAR(48 + FLOOR(RAND() * 10)),
    CHAR(48 + FLOOR(RAND() * 10))
)
WHERE EXISTS (
    SELECT 1 FROM users u2 
    WHERE u2.support_pin = u1.support_pin 
    AND u2.id != u1.id
);
