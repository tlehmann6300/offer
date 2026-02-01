-- Migration Script: Add Alumni Roles and New Locations
-- This script updates existing installations to support the new features
-- Run this script on your existing databases

-- ============================================
-- CONTENT DATABASE (dbs15161271) MIGRATION
-- ============================================

USE dbs15161271;

-- Add new locations H-1.88 and H-1.87
INSERT INTO locations (name, description) VALUES 
('H-1.88', 'Lagerraum H-1.88'),
('H-1.87', 'Lagerraum H-1.87')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- ============================================
-- USER DATABASE (dbs15253086) MIGRATION
-- ============================================

USE dbs15253086;

-- Step 1: Add is_alumni_validated column if it doesn't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_alumni_validated TINYINT(1) NOT NULL DEFAULT 0 
AFTER tfa_enabled;

-- Step 2: Modify role enum to include alumni and alumni_board
-- Note: This requires recreating the column with the new enum values
-- First, create a temporary column
ALTER TABLE users 
ADD COLUMN role_temp ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni') NOT NULL DEFAULT 'member';

-- Copy existing values
UPDATE users SET role_temp = role;

-- Drop old column and rename new one
ALTER TABLE users DROP COLUMN role;
ALTER TABLE users CHANGE COLUMN role_temp role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni') NOT NULL DEFAULT 'member';

-- Step 3: Update invitation_tokens table role enum
-- Create temporary column
ALTER TABLE invitation_tokens 
ADD COLUMN role_temp ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni') NOT NULL DEFAULT 'member';

-- Copy existing values
UPDATE invitation_tokens SET role_temp = role;

-- Drop old column and rename new one
ALTER TABLE invitation_tokens DROP COLUMN role;
ALTER TABLE invitation_tokens CHANGE COLUMN role_temp role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni') NOT NULL DEFAULT 'member';

-- ============================================
-- VERIFICATION
-- ============================================

-- Verify new locations were added
SELECT 'Locations check:' as 'Status';
SELECT COUNT(*) as 'Total Locations' FROM dbs15161271.locations;
SELECT * FROM dbs15161271.locations WHERE name IN ('H-1.88', 'H-1.87');

-- Verify user table changes
SELECT 'User table check:' as 'Status';
DESCRIBE dbs15253086.users;

-- Verify invitation_tokens table changes
SELECT 'Invitation tokens table check:' as 'Status';
DESCRIBE dbs15253086.invitation_tokens;

SELECT 'Migration completed successfully!' as 'Status';
