-- ============================================
-- ADD TFA_SECRET COLUMN TO USERS TABLE
-- ============================================
-- This migration adds the tfa_secret column to the users table
-- for storing 2FA secrets used with Google Authenticator
-- ============================================

USE dbs15253086;

-- Add tfa_secret column to users table
ALTER TABLE users 
ADD COLUMN tfa_secret VARCHAR(255) DEFAULT NULL 
COMMENT 'Two-factor authentication secret for Google Authenticator'
AFTER tfa_enabled;

-- Verify the column was added
DESCRIBE users;
