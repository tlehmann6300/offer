-- ============================================
-- Add profile_complete flag to users table
-- ============================================
-- This migration adds a profile_complete flag to track whether users
-- have completed their initial profile setup (first_name + last_name)
-- 
-- Usage: Run this SQL against the user database (dbs15253086)
-- ============================================

-- Add profile_complete column (default 1 for existing users to not disrupt current users)
ALTER TABLE users 
ADD COLUMN profile_complete BOOLEAN NOT NULL DEFAULT 1 
COMMENT 'Flag to track if user has completed initial profile setup (first_name + last_name)';

-- Create index for faster lookups
CREATE INDEX idx_profile_complete ON users(profile_complete);

-- Note: New users created after this migration should have profile_complete set to 0
-- until they complete their first_name and last_name in the alumni_profiles table
