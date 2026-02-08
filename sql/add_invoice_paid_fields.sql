-- Add paid_at and paid_by_user_id columns to invoices table
-- This migration adds support for tracking when an invoice was marked as paid and by whom

ALTER TABLE invoices 
ADD COLUMN paid_at DATETIME DEFAULT NULL AFTER status,
ADD COLUMN paid_by_user_id INT UNSIGNED DEFAULT NULL AFTER paid_at,
ADD INDEX idx_paid_at (paid_at);
