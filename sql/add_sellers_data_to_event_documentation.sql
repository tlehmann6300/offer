-- Add sellers_data column to event_documentation table
-- This column will store JSON array of seller entries with seller name, items, quantity, and revenue

ALTER TABLE event_documentation 
ADD COLUMN sellers_data JSON DEFAULT NULL COMMENT 'JSON array of seller entries with name, items, quantity, and revenue';
