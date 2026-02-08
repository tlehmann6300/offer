-- ============================================
-- MIGRATION SCRIPT: Fix Schema Mismatches
-- ============================================
-- Run this script to add missing columns to existing databases
-- Date: 2026-02-08
-- ============================================

-- Fix Content DB (dbs15161271)
-- ============================================

-- Add external_link column to blog_posts if it doesn't exist
ALTER TABLE blog_posts 
ADD COLUMN IF NOT EXISTS external_link VARCHAR(255) DEFAULT NULL 
AFTER image_path;

-- Fix Invoice DB (dbs15251284)
-- ============================================

-- Add updated_at column to invoices if it doesn't exist
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
AFTER created_at;

-- Rename 'reason' column to 'rejection_reason' if it exists (MySQL 8.0+)
-- Note: This will fail silently if the column doesn't exist or is already named rejection_reason
-- If your MySQL version doesn't support IF EXISTS, comment this out and run manually if needed
ALTER TABLE invoices 
CHANGE COLUMN IF EXISTS reason rejection_reason TEXT DEFAULT NULL;

-- For older MySQL versions, use this instead (uncomment if needed):
-- ALTER TABLE invoices CHANGE COLUMN reason rejection_reason TEXT DEFAULT NULL;
