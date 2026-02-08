-- Add paid_at and paid_by_user_id columns to invoices table
-- This migration adds support for tracking when an invoice was marked as paid and by whom

ALTER TABLE invoices 
ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER status,
ADD COLUMN paid_by_user_id INT UNSIGNED DEFAULT NULL AFTER paid_at,
ADD INDEX idx_paid_at (paid_at);

-- Note: Foreign key constraint to users table is not added here because:
-- 1. The invoices table is in a different database (dbs15251284) than users table (dbs15253086)
-- 2. Cross-database foreign keys are not supported in MySQL
-- 3. Application-level validation ensures data integrity
