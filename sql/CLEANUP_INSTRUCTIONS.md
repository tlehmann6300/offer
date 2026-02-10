# SQL Cleanup Script - Usage Instructions

## Overview

The `cleanup_sql.php` script safely removes all SQL files from the `sql/` directory **except** the three master database schema files.

## Master Files (Protected)

These files will **NEVER** be deleted by the script:
- `dbs15161271.sql` - Content Database (events, projects, inventory, blog, etc.)
- `dbs15251284.sql` - Invoice Database (invoices table)
- `dbs15253086.sql` - User Database (users, sessions, invitations)

## Files to be Removed

The following migration/temporary files will be deleted:
- `create_inventory_checkouts.sql`
- `migration_add_birthday_gender.sql`
- `migration_add_board_role_types.sql`
- `migration_add_used_at_column.sql`

## How to Use

### 1. Review What Will Be Deleted

Run the script and answer "no" when prompted:

```bash
php cleanup_sql.php
```

The script will show you:
- Which files will be kept (the 3 master files)
- Which files will be deleted (migration files)

Answer "no" to cancel and review the list.

### 2. Execute the Cleanup

When ready to proceed, run the script again and answer "yes":

```bash
php cleanup_sql.php
```

When prompted:
```
Do you want to proceed with deletion? (yes/no):
```

Type `yes` and press Enter.

### 3. Verify Results

The script will output:
- ✓ Successfully deleted files
- ✗ Files that failed to delete (if any)
- Summary of kept files

## Example Output

```
SQL Cleanup Script
==================

Master files (will NOT be deleted):
  - dbs15161271.sql
  - dbs15251284.sql
  - dbs15253086.sql

Files to keep: 3
  ✓ dbs15161271.sql
  ✓ dbs15251284.sql
  ✓ dbs15253086.sql

Files to delete: 4
  ✗ create_inventory_checkouts.sql
  ✗ migration_add_birthday_gender.sql
  ✗ migration_add_board_role_types.sql
  ✗ migration_add_used_at_column.sql

Do you want to proceed with deletion? (yes/no): yes

Deleting files...
  ✓ Deleted: create_inventory_checkouts.sql
  ✓ Deleted: migration_add_birthday_gender.sql
  ✓ Deleted: migration_add_board_role_types.sql
  ✓ Deleted: migration_add_used_at_column.sql

Cleanup complete!
  Deleted: 4 file(s)
  Errors: 0 file(s)
  Kept: 3 master file(s)
```

## Safety Features

1. **Interactive Confirmation:** The script always asks for confirmation before deleting anything
2. **Master File Protection:** The three master files are hardcoded and cannot be deleted
3. **Detailed Output:** Shows exactly what will happen before and after execution
4. **Error Handling:** Reports any files that fail to delete

## After Cleanup

After running the cleanup script, only these files should remain in `sql/`:
- `dbs15161271.sql`
- `dbs15251284.sql`
- `dbs15253086.sql`

These are your master database schema files and should be used for fresh installations or reference.

## Need Help?

If you encounter any issues:
1. Check file permissions in the `sql/` directory
2. Review the SQL_AUDIT_REPORT.md for details about the database schemas
3. The script can be safely run multiple times - it will only affect SQL files
