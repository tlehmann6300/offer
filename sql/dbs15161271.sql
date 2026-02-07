-- ============================================
-- CONTENT DATABASE (dbs15161271) - ALL MIGRATIONS CONSOLIDATED
-- ============================================
-- This file contains all migration changes for the Content Database
-- Apply this file to apply all migrations to production
-- ============================================

-- Migration 1: Add image_path column to inventory_items table (if exists)
ALTER TABLE inventory_items 
ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL;

-- Migration 2: Add project type to projects table
-- Add type column (internal/external classification)
ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS type ENUM('internal', 'external') NOT NULL DEFAULT 'internal';

-- Add index for performance
ALTER TABLE projects 
ADD INDEX IF NOT EXISTS idx_type (type);

-- Migration 3: Add profile fields to alumni_profiles table
-- Add German fields for candidates/members
ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS studiengang VARCHAR(255) DEFAULT NULL 
COMMENT 'Field of study for candidates and members';

ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS semester VARCHAR(50) DEFAULT NULL 
COMMENT 'Current semester for candidates and members';

ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS angestrebter_abschluss VARCHAR(255) DEFAULT NULL 
COMMENT 'Desired degree for candidates and members';

-- Migration 4: Add English student fields to alumni_profiles table
ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS study_program VARCHAR(100) DEFAULT NULL 
COMMENT 'Study program (English)';

ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS degree VARCHAR(50) DEFAULT NULL 
COMMENT 'Degree type (e.g., B.Sc., M.Sc.)';

ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS graduation_year INT DEFAULT NULL 
COMMENT 'Year of graduation';

-- Migration 5: Add about_me field to alumni_profiles table
ALTER TABLE alumni_profiles 
ADD COLUMN IF NOT EXISTS about_me TEXT DEFAULT NULL 
COMMENT 'Personal description/bio for all users';

-- Migration 6: Make company and position nullable for candidates/members
ALTER TABLE alumni_profiles 
MODIFY COLUMN company VARCHAR(255) DEFAULT NULL 
COMMENT 'Company name - required for alumni, optional for candidates/members';

ALTER TABLE alumni_profiles 
MODIFY COLUMN position VARCHAR(255) DEFAULT NULL 
COMMENT 'Job position - required for alumni, optional for candidates/members';

-- ============================================
-- END OF CONTENT DATABASE MIGRATIONS
-- ============================================
