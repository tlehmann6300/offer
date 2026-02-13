-- ============================================
-- Add secondary_email and show_birthday fields
-- ============================================
-- This migration adds:
-- 1. secondary_email field to alumni_profiles (optional, profile-only email)
-- 2. show_birthday field to users table (visibility toggle for birthday)

-- Add secondary_email to alumni_profiles table
ALTER TABLE alumni_profiles
ADD COLUMN IF NOT EXISTS secondary_email VARCHAR(255) DEFAULT NULL COMMENT 'Optional secondary email address for profile display only'
AFTER email;

-- Add show_birthday to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS show_birthday BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Whether to display birthday publicly on profile'
AFTER birthday;
