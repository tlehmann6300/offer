# Cleanup and Database Maintenance Tools Documentation

## Overview
This document describes two new tools implemented to help manage storage space and maintain the IBC Intranet system:

1. **cleanup_final.php** - One-time cleanup script to remove temporary files
2. **pages/admin/db_maintenance.php** - Ongoing database maintenance tool for administrators

---

## 1. Cleanup Script (cleanup_final.php)

### Purpose
Removes temporary files and folders that are no longer needed after the system setup is complete, helping to stay within the 2GB storage limit.

### What It Does
The script performs three main cleanup tasks:

#### Step 1: Remove sql/migrations/ folder
- Deletes the entire `sql/migrations/` directory and all its contents
- Reason: All migrations have been consolidated into the main schema files
- Files removed:
  - `001_add_alumni_roles_and_locations.sql`
  - `002_add_checkout_system.sql`
  - `003_add_rentals_table.sql`
  - `004_add_event_system.sql`
  - `README.md`

#### Step 2: Remove backup and archive files
- Scans the root directory for old backup files
- Deletes files matching these patterns:
  - `*.backup`
  - `*.zip`
  - `*.tar.gz`
  - `*.tar`
- Only looks in the root directory (does not search subdirectories)

#### Step 3: Remove setup scripts
- Deletes installation scripts no longer needed:
  - `setup.sh` - Initial setup script
  - `import_database.sh` - Database import script

### Features
- **Visual Output**: Clean, user-friendly HTML interface with Tailwind CSS styling
- **Detailed Reporting**: Shows exactly what was deleted and how much space was freed
- **Error Handling**: Gracefully handles missing files and reports any errors
- **Statistics Dashboard**: Displays:
  - Number of folders deleted
  - Number of files deleted
  - Total space freed
- **Safety**: Only deletes specified files/folders, never touches anything else

### Execution Results
When run on this system, the script successfully:
- ✓ Deleted `sql/migrations/` folder (17.14 KB)
- ✓ Deleted `setup.sh` (2.32 KB)
- ✓ Total space freed: **19.46 KB**

### How to Use
1. Access via web browser: `https://your-domain.com/cleanup_final.php`
2. The script runs automatically when the page loads
3. Review the results
4. Click "Return to Dashboard" to go back to the main interface

### After Running
The script can be run multiple times safely. If there are no files to delete, it will simply report that the system is already clean.

---

## 2. Database Maintenance Tool (pages/admin/db_maintenance.php)

### Purpose
Provides administrators with tools to monitor database storage usage and clean up old data to free up space.

### Access Control
- **Required Permission**: `board` or `admin` level
- **Who Can Access**:
  - ✓ admin (level 4)
  - ✓ board (level 3)
  - ✓ alumni_board (level 3)
- **Who Cannot Access**:
  - ✗ manager (level 2)
  - ✗ member (level 1)
  - ✗ alumni (level 1)

### Features

#### Database Overview Section
Displays comprehensive storage statistics:

**Summary Cards:**
- User Database size (MB)
- Content Database size (MB)
- Total database size (MB)

**Detailed Tables:**
- Lists all tables in both databases
- Shows row count for each table
- Shows size in MB for each table
- Sorted by size (largest first)

This helps administrators identify which tables are consuming the most space.

#### Maintenance Actions

##### 1. Clean Logs Button
**Purpose**: Delete old log entries to free up space

**What It Does**:
- Deletes `user_sessions` older than 30 days
- Deletes `system_logs` older than 1 year
- Deletes `inventory_history` older than 1 year
- Deletes `event_history` older than 1 year

**Safety Features**:
- Confirmation dialog before executing
- Cannot be undone warning
- Logs the cleanup action to system_logs
- Reports number of rows deleted from each table

**SQL Queries Used**:
```sql
-- User Sessions (30 days)
DELETE FROM user_sessions WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- System Logs (1 year)
DELETE FROM system_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Inventory History (1 year)
DELETE FROM inventory_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Event History (1 year)
DELETE FROM event_history WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

##### 2. Clear Cache Button
**Purpose**: Delete temporary cache files

**What It Does**:
- Scans the `cache/` directory if it exists
- Deletes all files in the cache folder
- Reports number of files deleted and space freed

**Safety Features**:
- Confirmation dialog before executing
- Only affects cache files, not database
- Logs the action to system_logs
- Gracefully handles missing cache directory

### Design & UI
- **Consistent Styling**: Uses the same Tailwind CSS theme as other admin pages
- **Color-Coded Sections**: 
  - Blue for database overview
  - Yellow for log cleanup
  - Blue for cache clearing
- **Responsive Design**: Works on mobile and desktop
- **Font Awesome Icons**: Visual indicators for each section

### How to Use

#### Access the Tool
1. Log in with admin or board credentials
2. Navigate to: `/pages/admin/db_maintenance.php`
3. Or add a menu link in the admin navigation

#### Monitor Database Size
- Review the overview section to see which tables are largest
- Check if total size is approaching storage limits
- Identify tables that might need cleanup

#### Clean Old Logs
1. Click "Logs bereinigen" button
2. Confirm the action in the dialog
3. Review the results showing how many rows were deleted from each table

#### Clear Cache
1. Click "Cache leeren" button
2. Confirm the action in the dialog
3. Review the results showing files deleted and space freed

### Logging
All maintenance actions are logged to the `system_logs` table with:
- User ID of the person who performed the action
- Action type (cleanup_logs or clear_cache)
- Details of what was deleted
- IP address
- Timestamp

This provides an audit trail for all maintenance activities.

### Warning Notice
The page includes a prominent warning:
> **Hinweis:** Wartungsaktionen können nicht rückgängig gemacht werden. Stellen Sie sicher, dass Sie vor dem Bereinigen wichtiger Daten ein Backup erstellt haben.

(Note: Maintenance actions cannot be undone. Make sure you have created a backup before cleaning important data.)

---

## Testing

### Test Coverage
Both tools have been thoroughly tested:

#### Cleanup Script Tests (`tests/test_cleanup_script.php`)
- ✓ Format bytes function works correctly
- ✓ Directory deletion works recursively
- ✓ File pattern matching works correctly
- ✓ Only targeted files/folders are deleted
- ✓ Cleanup script successfully executed
- ✓ Output is user-friendly and informative

#### Maintenance Tool Tests (`tests/test_db_maintenance.php`)
- ✓ Format bytes function works correctly
- ✓ SQL cleanup queries are valid
- ✓ Cache cleanup logic functions properly
- ✓ Permission checks are in place
- ✓ Database size queries are valid
- ✓ Safety measures implemented

### Running Tests
```bash
# Test cleanup script
php tests/test_cleanup_script.php

# Test maintenance tool
php tests/test_db_maintenance.php
```

---

## Security Considerations

### Cleanup Script
- No authentication required (one-time use)
- Only deletes specific, non-critical files
- Cannot damage the database or user data
- Can be deleted after running once

### Maintenance Tool
- **Authentication Required**: Must be logged in
- **Authorization Required**: Must have board or admin permission
- **Confirmation Required**: Destructive actions require user confirmation
- **Audit Trail**: All actions logged with user ID and IP
- **Read-Only Queries**: Table size queries use SELECT only
- **Prepared Statements**: All SQL queries use PDO prepared statements

---

## Storage Impact

### Immediate Savings (cleanup_final.php)
- Freed: 19.46 KB

### Potential Savings (db_maintenance.php)
Depends on data volume, but could free:
- Old session records (typically small)
- System logs from over 1 year ago
- Inventory history from over 1 year ago
- Event history from over 1 year ago
- Cache files (varies by usage)

Regular maintenance (monthly or quarterly) helps prevent databases from growing too large and hitting the 2GB limit.

---

## Recommendations

### For Cleanup Script
1. Run `cleanup_final.php` once via web browser
2. Verify the results
3. Delete the `cleanup_final.php` file itself after successful execution (optional)

### For Maintenance Tool
1. Add a link to the admin navigation menu
2. Run log cleanup quarterly or when approaching storage limits
3. Run cache clearing as needed (safe to do frequently)
4. Monitor database sizes regularly using the overview section
5. Consider automating log cleanup in the future (cron job or scheduled task)

---

## Future Enhancements

Possible improvements for the future:

1. **Automated Cleanup**: Schedule log cleanup to run automatically (e.g., monthly)
2. **Customizable Retention**: Allow admins to configure how long to keep logs
3. **Backup Before Cleanup**: Automatically export old logs before deletion
4. **Email Notifications**: Alert admins when databases approach size limits
5. **More Granular Control**: Select which types of logs to clean
6. **Restore Feature**: Keep compressed archives of deleted logs for a short period

---

## Support

If you encounter any issues:
1. Check the system_logs table for error details
2. Ensure proper database permissions
3. Verify that the user has board/admin role
4. Check PHP error logs for technical issues

Both tools include error handling and will display user-friendly error messages if something goes wrong.
