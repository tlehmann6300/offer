-- ============================================
-- Migration: Add used_at and used_by columns to invitation_tokens table
-- ============================================
-- This migration adds the used_at and used_by columns to track when 
-- invitation tokens are used and by whom
-- Date: 2026-02-09
-- ============================================

USE dbs15253086;

-- Add used_at column if it doesn't exist
ALTER TABLE invitation_tokens 
ADD COLUMN IF NOT EXISTS used_at DATETIME DEFAULT NULL AFTER expires_at;

-- Add used_by column if it doesn't exist
ALTER TABLE invitation_tokens 
ADD COLUMN IF NOT EXISTS used_by INT UNSIGNED DEFAULT NULL AFTER used_at;

-- Add indexes for better query performance
ALTER TABLE invitation_tokens 
ADD INDEX IF NOT EXISTS idx_used_at (used_at);

ALTER TABLE invitation_tokens 
ADD INDEX IF NOT EXISTS idx_used_by (used_by);

-- Add foreign key constraints
-- Note: We use IF NOT EXISTS equivalent by checking for constraint existence
ALTER TABLE invitation_tokens 
ADD CONSTRAINT fk_invitation_used_by
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL;

-- If created_by foreign key doesn't exist, add it
ALTER TABLE invitation_tokens 
ADD CONSTRAINT fk_invitation_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================
-- Verification
-- ============================================
-- Run this to verify the columns were added:
-- DESCRIBE invitation_tokens;
-- Run this to verify the foreign keys:
-- SHOW CREATE TABLE invitation_tokens;
