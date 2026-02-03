-- Migration: Add fields for inventory import functionality
-- Date: 2026-02-03
-- Description: Adds serial_number, status, and purchase_date fields to inventory table

-- Add serial_number column if it doesn't exist
ALTER TABLE inventory 
ADD COLUMN serial_number VARCHAR(100) DEFAULT NULL AFTER description;

-- Add status column if it doesn't exist
ALTER TABLE inventory 
ADD COLUMN status ENUM('available', 'in_use', 'maintenance', 'retired') NOT NULL DEFAULT 'available' AFTER location_id;

-- Add purchase_date column if it doesn't exist
ALTER TABLE inventory 
ADD COLUMN purchase_date DATE DEFAULT NULL AFTER unit_price;

-- Add index on serial_number for faster lookups during import
CREATE INDEX idx_serial_number ON inventory(serial_number);
