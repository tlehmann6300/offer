-- Add last_reminder_sent_at column to users table
-- This column is used to track when profile reminder emails were sent to users
-- to avoid sending duplicate reminders (spam protection)

ALTER TABLE users 
ADD COLUMN last_reminder_sent_at DATETIME DEFAULT NULL 
COMMENT 'Timestamp when the last profile reminder email was sent to the user';

-- Add index for better query performance
ALTER TABLE users 
ADD INDEX idx_last_reminder_sent_at (last_reminder_sent_at);
