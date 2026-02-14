-- ============================================
-- Add azure_oid column to users table
-- ============================================
-- This migration adds a column to store the Azure Object ID (oid)
-- from Microsoft Entra ID for each user, enabling direct role
-- synchronization with Azure without relying on email lookups.
-- ============================================

ALTER TABLE users 
ADD COLUMN azure_oid VARCHAR(255) DEFAULT NULL 
COMMENT 'Azure Object ID (oid) from Microsoft Entra ID' 
AFTER azure_roles;

-- Add index for faster lookups by azure_oid
ALTER TABLE users 
ADD INDEX idx_azure_oid (azure_oid);
