-- Migration Script: Add Rentals Table
-- This creates the rentals table as specified in requirements
-- Note: This complements the existing inventory_checkouts table
-- Run this script on the content database

-- ============================================
-- CONTENT DATABASE (dbs15161271) MIGRATION
-- ============================================

USE dbs15161271;

-- Create rentals table as per requirements
-- This table provides a simplified rental tracking system
CREATE TABLE IF NOT EXISTS rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    rented_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return DATE DEFAULT NULL,
    actual_return TIMESTAMP DEFAULT NULL,
    status ENUM('active', 'returned', 'overdue', 'defective') NOT NULL DEFAULT 'active',
    defect_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_item_id (item_id),
    INDEX idx_status (status),
    INDEX idx_rented_at (rented_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VERIFICATION
-- ============================================

-- Verify rentals table was created
SELECT 'Rentals table check:' as 'Status';
DESCRIBE rentals;

SELECT 'Migration 003 completed successfully!' as 'Status';
