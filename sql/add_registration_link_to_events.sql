-- Add registration_link column to events table
-- This allows events to have external registration (e.g., Microsoft Forms)

ALTER TABLE events 
ADD COLUMN registration_link TEXT DEFAULT NULL 
COMMENT 'External registration link (e.g., Microsoft Forms URL) for event registration' 
AFTER external_link;
