-- Migration: Adapt inventory for EasyVerein integration
-- Date: 2026-02-05
-- Description: Adds EasyVerein synchronization fields to inventory table for hybrid master data management

-- Add easyverein_id column for mapping to EasyVerein items
ALTER TABLE inventory 
ADD COLUMN easyverein_id VARCHAR(100) DEFAULT NULL AFTER id;

-- Add unique constraint to easyverein_id to prevent duplicates
ALTER TABLE inventory
ADD UNIQUE INDEX idx_easyverein_id (easyverein_id);

-- Add last_synced_at column to track synchronization timestamp
ALTER TABLE inventory 
ADD COLUMN last_synced_at DATETIME DEFAULT NULL AFTER updated_at;

-- Add is_archived_in_easyverein column to handle remotely deleted items
ALTER TABLE inventory 
ADD COLUMN is_archived_in_easyverein BOOLEAN NOT NULL DEFAULT 0 AFTER last_synced_at;
