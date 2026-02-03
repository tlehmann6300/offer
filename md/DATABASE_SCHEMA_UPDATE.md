# Database Schema Update - February 2026

## Overview

This update adds new database columns to support enhanced event functionality:
- **maps_link**: Store Google Maps links for event locations
- **registration_start**: Define when event registration opens
- **registration_end**: Define when event registration closes
- **image_path**: Store uploaded event images

## Problem Solved

The application code (Event.php) was updated to use these new database columns, but the database schema itself had not been updated yet. This caused the error:
```
Unknown column 'maps_link' in 'field list'
```

## Solution Components

### 1. Migration Scripts

Two migration scripts are provided to update the database:

#### A. `sql/migrate_add_event_fields.php` (Recommended)
- Comprehensive migration script
- Checks for existing columns before adding
- Safe to run multiple times
- Adds: `maps_link`, `registration_start`, `registration_end`
- Also updates `location` column length if needed
- Provides detailed output of all operations

#### B. `fix_event_db.php` (Quick Fix)
- Simple hotfix script
- Adds: `maps_link`, `image_path`
- **Self-deletes after execution**
- Useful for quick fixes

### 2. Upload Directory Structure

Created `/uploads/events/` directory for storing event images:
- Directory structure tracked in Git via `.gitkeep`
- Uploaded files excluded from Git via `.gitignore`
- Requires write permissions (755 or 777) on the server

### 3. Verification Tool

`verify_db_schema.php` - Web-based verification script:
- Shows which columns exist
- Identifies missing columns
- Checks upload directory permissions
- Provides links to migration scripts
- Safe to run anytime (read-only)

### 4. Theme Verification

The `assets/css/theme.css` already contains IBC Corporate Identity colors:
- IBC Green: `#00a651`
- IBC Blue: `#0066b3`
- IBC Accent: `#ff6b35`

No changes needed for theme colors.

## Deployment Checklist

When deploying to production:

- [ ] Upload all files to server
- [ ] Run verification: `https://your-domain.de/verify_db_schema.php`
- [ ] Run migration: `https://your-domain.de/sql/migrate_add_event_fields.php`
- [ ] Verify uploads directory exists and has write permissions
- [ ] Test creating/editing an event with Maps link
- [ ] Test image upload functionality
- [ ] Delete verification and migration scripts
- [ ] Verify theme colors are displaying correctly

## Technical Details

### Database Schema Changes

```sql
-- Add Google Maps link column
ALTER TABLE events 
ADD COLUMN maps_link VARCHAR(255) DEFAULT NULL 
AFTER location;

-- Add registration date columns
ALTER TABLE events 
ADD COLUMN registration_start DATETIME DEFAULT NULL 
AFTER end_time;

ALTER TABLE events 
ADD COLUMN registration_end DATETIME DEFAULT NULL 
AFTER registration_start;

-- Update location column length (if needed)
ALTER TABLE events 
MODIFY COLUMN location VARCHAR(255) DEFAULT NULL;
```

### Event Status Calculation

The event status is now automatically calculated based on:
1. Current date/time
2. Event start/end times
3. Registration start/end times (if set)

Status values:
- `planned`: Before registration starts (if registration dates set)
- `open`: During registration period or before event start (if no registration dates)
- `closed`: After registration ends but before event starts
- `running`: Event is currently happening
- `past`: Event has ended

### File Upload Configuration

Event images are uploaded to `/uploads/events/` with these settings:
- Max file size: Configured in PHP (typically 2MB-8MB)
- Allowed formats: JPG, JPEG, PNG, GIF, WEBP
- Naming: Secured filenames to prevent path traversal
- Storage: Files stored with unique names

## Troubleshooting

### "Unknown column 'maps_link'" Error
**Cause:** Database migration not run yet  
**Solution:** Run `sql/migrate_add_event_fields.php`

### "Failed to write file" Error
**Cause:** Upload directory doesn't exist or lacks permissions  
**Solution:** 
```bash
mkdir -p /path/to/uploads/events
chmod 755 /path/to/uploads/events
```

### Theme Shows Old Colors
**Cause:** Browser cache  
**Solution:** Hard refresh (Ctrl+F5 or Cmd+Shift+R) or clear cache

## Files Modified

### New Files
- `DEPLOYMENT_INSTRUCTIONS.md` - Deployment guide
- `verify_db_schema.php` - Database verification tool
- `uploads/events/.gitkeep` - Directory structure marker
- `DATABASE_SCHEMA_UPDATE.md` - This file

### Modified Files
- `.gitignore` - Added uploads/events exclusion pattern

### Existing Files (No Changes)
- `sql/migrate_add_event_fields.php` - Already present
- `fix_event_db.php` - Already present
- `assets/css/theme.css` - Already has IBC colors
- `includes/models/Event.php` - Already uses new columns

## Support

If you encounter issues during deployment:
1. Check the `verify_db_schema.php` output
2. Review server error logs
3. Ensure database credentials in `.env` are correct
4. Verify file/directory permissions

## Post-Deployment

After successful deployment:
1. ✅ Delete `verify_db_schema.php`
2. ✅ Delete `sql/migrate_add_event_fields.php`
3. ✅ Delete `fix_event_db.php`
4. ✅ Test event creation/editing
5. ✅ Test image uploads
6. ✅ Verify Maps links work
