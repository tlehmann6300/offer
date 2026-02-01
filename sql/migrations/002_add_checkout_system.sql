-- Migration Script: Add Checkout/Check-in System
-- This script adds the checkout functionality for inventory management
-- Run this script on the content database

-- ============================================
-- CONTENT DATABASE (dbs15161271) MIGRATION
-- ============================================

USE dbs15161271;

-- Add new locations for Furtwangen
INSERT INTO locations (name, description) VALUES 
('Furtwangen H-Bau -1.87', 'Lagerraum Furtwangen H-Bau -1.87'),
('Furtwangen H-Bau -1.88', 'Lagerraum Furtwangen H-Bau -1.88')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Create inventory_checkouts table
CREATE TABLE IF NOT EXISTS inventory_checkouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    destination VARCHAR(255) DEFAULT NULL,
    checkout_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE DEFAULT NULL,
    return_date TIMESTAMP DEFAULT NULL,
    returned_quantity INT DEFAULT NULL,
    defective_quantity INT DEFAULT 0,
    defective_reason TEXT DEFAULT NULL,
    status ENUM('checked_out', 'returned', 'partially_returned', 'overdue') NOT NULL DEFAULT 'checked_out',
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE,
    INDEX idx_item_id (item_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_checkout_date (checkout_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update inventory_history to support new change types
-- First, create a temporary column with the expanded enum
ALTER TABLE inventory_history 
ADD COLUMN change_type_temp ENUM('adjustment', 'create', 'update', 'delete', 'checkout', 'checkin', 'writeoff') NOT NULL DEFAULT 'adjustment';

-- Copy existing values
UPDATE inventory_history SET change_type_temp = change_type;

-- Drop old column and rename new one
ALTER TABLE inventory_history DROP COLUMN change_type;
ALTER TABLE inventory_history CHANGE COLUMN change_type_temp change_type ENUM('adjustment', 'create', 'update', 'delete', 'checkout', 'checkin', 'writeoff') NOT NULL DEFAULT 'adjustment';

-- ============================================
-- VERIFICATION
-- ============================================

-- Verify new locations were added
SELECT 'Locations check:' as 'Status';
SELECT COUNT(*) as 'Total Locations' FROM locations;
SELECT * FROM locations WHERE name LIKE '%Furtwangen%';

-- Verify checkouts table was created
SELECT 'Checkouts table check:' as 'Status';
DESCRIBE inventory_checkouts;

-- Verify inventory_history changes
SELECT 'Inventory history table check:' as 'Status';
DESCRIBE inventory_history;

SELECT 'Migration completed successfully!' as 'Status';
