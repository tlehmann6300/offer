-- Add deleted_at column to users table for soft deletes
-- This column is used to mark users as deleted without removing them from the database
-- NULL means the user is active, a timestamp means the user is deleted

ALTER TABLE users 
ADD COLUMN deleted_at DATETIME DEFAULT NULL 
COMMENT 'Timestamp when the user was soft deleted (NULL = active)';

-- Add index for better query performance
ALTER TABLE users 
ADD INDEX idx_deleted_at (deleted_at);
