-- Add theme_preference column to users table
-- This migration adds support for theme selection (auto, light, dark)

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS theme_preference VARCHAR(10) DEFAULT 'auto' 
COMMENT 'User theme preference: auto, light, or dark';

-- Update existing users to have 'auto' as default if null
UPDATE users SET theme_preference = 'auto' WHERE theme_preference IS NULL;
