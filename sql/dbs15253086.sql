-- ============================================
-- USER DATABASE (dbs15253086) - ALL MIGRATIONS CONSOLIDATED
-- ============================================
-- This file contains all migration changes for the User Database
-- Apply this file to apply all migrations to production
-- ============================================

-- Migration 1: Add 'candidate' role to users table
-- Changes the role ENUM to include: 'admin', 'board', 'head', 'member', 'alumni', 'candidate'
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
NOT NULL DEFAULT 'member';

-- Migration 2: Add 'candidate' and 'alumni_board' role to user_invitations table
ALTER TABLE user_invitations 
MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
NOT NULL DEFAULT 'member';

-- Migration 3: Add security features to users table
-- Add failed login attempts tracking
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS failed_login_attempts INT NOT NULL DEFAULT 0;

-- Add temporary lock timestamp
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL;

-- Add permanent lock flag
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_locked_permanently BOOLEAN NOT NULL DEFAULT 0;

-- Migration 4: Add notification preferences to users table
-- Add project notification preference (default TRUE - opt-out model)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS notify_new_projects BOOLEAN NOT NULL DEFAULT 1;

-- Add event notification preference (default TRUE - opt-out model)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS notify_new_events BOOLEAN NOT NULL DEFAULT 1;

-- Migration 5: Add expires_at column to user_invitations table
ALTER TABLE user_invitations 
ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL;

-- Migration 6: Create invoices table for invoice management
CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to users table',
    description VARCHAR(255) NOT NULL COMMENT 'Short purpose description',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Invoice amount',
    date_of_receipt DATE NOT NULL COMMENT 'Date the receipt was received',
    file_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded receipt image/pdf',
    status ENUM('pending', 'approved', 'rejected', 'paid') 
        NOT NULL DEFAULT 'pending' 
        COMMENT 'Invoice processing status',
    rejection_reason TEXT DEFAULT NULL COMMENT 'Reason for rejection if applicable',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Invoice creation timestamp',
    
    -- Foreign key constraint
    CONSTRAINT fk_invoice_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_date_of_receipt (date_of_receipt),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Invoice management system for receipt tracking and approval';

-- ============================================
-- END OF USER DATABASE MIGRATIONS
-- ============================================
