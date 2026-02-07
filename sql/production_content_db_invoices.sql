-- ============================================
-- PRODUCTION CONTENT DATABASE - INVOICE SYSTEM
-- ============================================
-- SQL Schema for the Production Content Database (Invoices)
-- This file should be run on the production Content DB after configuration
-- 
-- Production Credentials (from .env after setup_production_db.php):
-- DB_CONTENT_HOST = db5019505323.hosting-data.io
-- DB_CONTENT_PORT = 3306
-- DB_CONTENT_USER = dbu387360
-- DB_CONTENT_PASS = F9!qR7#L@2mZ$8KAS44
-- DB_CONTENT_NAME = (provided by user during setup)
-- ============================================

-- ============================================
-- INVOICE MANAGEMENT SYSTEM
-- ============================================

-- Main invoices table for receipt tracking and approval
CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT 'Foreign key to users table in User DB',
    description VARCHAR(255) NOT NULL COMMENT 'Short purpose description of the expense',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Invoice amount in EUR',
    date_of_receipt DATE NOT NULL COMMENT 'Date the receipt was received',
    file_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded receipt image/pdf',
    status ENUM('pending', 'approved', 'rejected', 'paid') 
        NOT NULL DEFAULT 'pending' 
        COMMENT 'Invoice processing status',
    rejection_reason TEXT DEFAULT NULL COMMENT 'Reason for rejection if status is rejected',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Invoice creation timestamp',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
    
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
-- INVOICE HISTORY/AUDIT LOG (Optional)
-- ============================================

-- Track changes to invoices for audit purposes
CREATE TABLE IF NOT EXISTS invoice_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL COMMENT 'User who made the change',
    action VARCHAR(50) NOT NULL COMMENT 'Action performed: created, updated, approved, rejected, paid',
    old_status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT NULL,
    new_status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT NULL,
    comment TEXT DEFAULT NULL COMMENT 'Additional notes about the change',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_user_id (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit log for invoice changes';

-- ============================================
-- INVOICE CATEGORIES (Optional Enhancement)
-- ============================================

-- Categorize invoice expenses for better reporting
CREATE TABLE IF NOT EXISTS invoice_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    budget_limit DECIMAL(10,2) DEFAULT NULL COMMENT 'Optional budget limit for this category',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Categories for invoice expenses';

-- Add category_id to invoices table (optional)
-- ALTER TABLE invoices 
-- ADD COLUMN category_id INT UNSIGNED DEFAULT NULL COMMENT 'Category of the expense',
-- ADD FOREIGN KEY (category_id) REFERENCES invoice_categories(id) ON DELETE SET NULL,
-- ADD INDEX idx_category_id (category_id);

-- ============================================
-- DEFAULT CATEGORIES (Optional)
-- ============================================

INSERT IGNORE INTO invoice_categories (name, description) VALUES 
('Reisekosten', 'Travel expenses including transportation and accommodation'),
('Büromaterial', 'Office supplies and materials'),
('Catering', 'Food and beverages for events'),
('Marketing', 'Marketing and promotional materials'),
('IT & Software', 'Technology and software expenses'),
('Sonstiges', 'Other miscellaneous expenses');

-- ============================================
-- NOTES
-- ============================================
-- 
-- After running this schema:
-- 1. Ensure the uploads/invoices/ directory exists with proper permissions (0777)
-- 2. Add .htaccess to uploads/invoices/ to prevent directory listing
-- 3. Run any necessary migrations from sql/migrate_invoice_module.php
-- 4. Verify user roles include 'alumni_board' in the User database
-- 
-- Related files:
-- - api/submit_invoice.php: Submit new invoices
-- - api/update_invoice_status.php: Approve/reject invoices
-- - api/export_invoices.php: Export invoice data
-- - pages/invoices.php: Invoice management UI
-- 
-- ============================================
