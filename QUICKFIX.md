# Quick Fix Guide: Dashboard & Polls Errors

## Problems

### Error 1: Dashboard Events Error
```
Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'e.needs_helpers' in 'where clause'
```

### Error 2: Dashboard Polls Error
```
Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'p.is_active' in 'where clause' in /homepages/34/d795569457/htdocs/intra/pages/dashboard/index.php:382
```

## Solution (3 steps)

### Step 1: Deploy Latest Code
Pull and deploy the latest code from this repository to production.

### Step 2: Run Database Update
On the production server, run:
```bash
cd /path/to/project
php update_database_schema.php
```

You should see output like:
```
Executing: Add needs_helpers column to events table
✓ SUCCESS: Add needs_helpers column to events table

Executing: Add target_groups column to polls table
✓ SUCCESS: Add target_groups column to polls table

Executing: Add is_active column to polls table
✓ SUCCESS: Add is_active column to polls table

Executing: Add end_date column to polls table
✓ SUCCESS: Add end_date column to polls table
```

### Step 3: Verify
Run the verification script:
```bash
php verify_database_schema.php
```

You should see:
```
✓ All schema checks passed!
Your database schema is up to date.
```

## Done!
Refresh the dashboard in your browser. The error should be gone.

## If You Need Help
1. Check the full guide: [DEPLOYMENT.md](DEPLOYMENT.md)
2. Check error logs in the `/logs/` directory
3. Contact the development team

## Technical Details

### Events Table Fix
- The `needs_helpers` column was added to the events table schema
- The error handling code already exists (from PR #535)

### Polls Table Fix
- Three missing columns were added to the polls table schema:
  - `target_groups` - JSON array for audience filtering
  - `is_active` - Boolean flag to show/hide polls (default: 1)
  - `end_date` - DATETIME for poll expiration
- Indexes added for `is_active` and `end_date` for query performance
- Fixes dashboard, polls list, poll view, and poll creation pages

### General Notes
- Running `update_database_schema.php` adds all missing columns
- The script is safe to run multiple times (skips existing columns)
- All changes are backward compatible
