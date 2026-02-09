-- ============================================
-- Migration: Add birthday and gender columns to users table
-- ============================================
-- This migration adds the birthday (DATE) and gender (ENUM) columns
-- to the users table for birthday wishes functionality.
-- ============================================

-- Add birthday column (DATE) to users table
ALTER TABLE users 
ADD COLUMN birthday DATE DEFAULT NULL 
COMMENT 'User birthday for birthday wishes';

-- Add gender column (ENUM) to users table
ALTER TABLE users 
ADD COLUMN gender ENUM('m', 'f', 'd') DEFAULT NULL 
COMMENT 'User gender: m=male, f=female, d=diverse';

-- Add indexes for birthday queries
ALTER TABLE users ADD INDEX idx_birthday (birthday);
