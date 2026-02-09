-- ============================================
-- MIGRATION: Add Board Role Types
-- ============================================
-- This migration adds new board role types:
-- - vorstand_intern (Board Internal)
-- - vorstand_extern (Board External) 
-- - vorstand_finanzen_recht (Board Finance and Legal)
-- 
-- Only vorstand_finanzen_recht will have permission to mark invoices as paid
-- ============================================

-- Update users table to add new board role types
ALTER TABLE users 
MODIFY COLUMN role ENUM(
    'board', 
    'vorstand_intern',
    'vorstand_extern', 
    'vorstand_finanzen_recht',
    'head', 
    'member', 
    'alumni', 
    'candidate', 
    'alumni_board',
    'honorary_member'
) NOT NULL DEFAULT 'member';

-- Update invitation_tokens table to add new board role types
ALTER TABLE invitation_tokens 
MODIFY COLUMN role ENUM(
    'board',
    'vorstand_intern',
    'vorstand_extern',
    'vorstand_finanzen_recht', 
    'head',
    'member',
    'alumni',
    'candidate',
    'alumni_board',
    'honorary_member'
) NOT NULL DEFAULT 'member';

-- Optional: Migrate existing 'board' users to 'vorstand_intern' as default
-- Uncomment if you want to automatically migrate existing board members
-- UPDATE users SET role = 'vorstand_intern' WHERE role = 'board';
