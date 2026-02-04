-- Migration: Add documentation field to projects table
-- Date: 2026-02-04
-- Description: Adds documentation field to store project completion reports

ALTER TABLE projects 
ADD COLUMN documentation TEXT DEFAULT NULL 
COMMENT 'Project completion documentation/report';
