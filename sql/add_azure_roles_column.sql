-- Add azure_roles column to users table
-- This migration adds a JSON column to store the original Microsoft Entra ID roles
-- Allows displaying the user's actual Entra roles in the profile

ALTER TABLE users 
ADD COLUMN azure_roles JSON DEFAULT NULL 
COMMENT 'Original Microsoft Entra ID roles from Azure AD authentication';

-- Add index for better query performance if needed in the future
CREATE INDEX idx_azure_roles ON users((CAST(azure_roles AS CHAR(255)) COLLATE utf8mb4_bin));
