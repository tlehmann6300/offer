# Quick Fix Guide: Dashboard Error

## Problem
Dashboard shows this error:
```
Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'e.needs_helpers' in 'where clause'
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
- The `needs_helpers` column was added to the events table schema
- The error handling code already exists (from PR #535)
- This PR adds documentation and tooling to guide the fix
- Running `update_database_schema.php` adds the missing column
- The script is safe to run multiple times (skips existing columns)
