-- Add microsoft_forms_url column to polls table
-- This allows integration with Microsoft Forms for surveys

ALTER TABLE polls 
ADD COLUMN microsoft_forms_url TEXT DEFAULT NULL 
COMMENT 'Microsoft Forms embed URL or direct link for external survey integration';
