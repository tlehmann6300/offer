-- Migration: Add last_reminder_sent_at column to alumni_profiles table
-- Date: 2026-02-05
-- Description: Adds last_reminder_sent_at field to track when annual reminder emails were sent to prevent spamming

ALTER TABLE alumni_profiles 
ADD COLUMN last_reminder_sent_at DATETIME DEFAULT NULL 
COMMENT 'Tracks when the annual reminder email was sent to this alumni';
