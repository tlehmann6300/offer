-- Add azure_roles column to users table
-- This migration adds a JSON column to store the original Microsoft Entra ID roles
-- Allows displaying the user's actual Entra roles in the profile

ALTER TABLE users 
ADD COLUMN azure_roles JSON DEFAULT NULL 
COMMENT 'Original Microsoft Entra ID roles from Azure AD authentication';
