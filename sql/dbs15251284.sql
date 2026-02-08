-- ============================================
-- INVOICE DATABASE (dbs15251284) - DEFINITIVE SCHEMA
-- ============================================
-- This file contains ONLY the invoices table
-- Database: invoice_db (dbs15251284)
-- ============================================

-- ============================================
-- INVOICES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    date_of_receipt DATE NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'paid') 
        NOT NULL DEFAULT 'pending',
    rejection_reason TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_date_of_receipt (date_of_receipt),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Invoice management system';
