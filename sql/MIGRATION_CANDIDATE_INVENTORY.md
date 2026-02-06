# Candidate Role and Inventory Migration

## Overview
This migration adds the "candidate" (Anwärter) role to the user system and ensures the inventory table has the required image_path column.

## What This Migration Does

### 1. User Database Changes
- **users table**: Adds 'candidate' to the role ENUM
- **invitation_tokens table**: Adds 'candidate' to the role ENUM

The complete role list becomes:
- `admin` - System administrators
- `board` - Board members  
- `head` - Department heads
- `member` - Regular members
- `alumni` - Alumni
- `candidate` - Candidates (Anwärter) ⭐ NEW

### 2. Content Database Changes - Inventory Table

The migration automatically detects which inventory table schema is in use:
- `inventory_items` (full schema) 
- `inventory` (modern schema)

#### Column Updates
- **image_path**: Ensures column exists as `VARCHAR(255) DEFAULT NULL`
  - Fixes 'Column not found' error when image_path is missing

## How to Run

### Method 1: Via Deploy Migrations Script
The migration is automatically included in the deployment script:
```
https://your-domain.de/deploy_migrations.php
```

### Method 2: Run Directly
```bash
php sql/migrate_add_candidate_role_fix_inventory.php
```

### Method 3: Run Individual SQL (Manual)
If you prefer to run SQL directly in phpMyAdmin or similar:

```sql
-- 1. Add candidate role to users table
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
NOT NULL DEFAULT 'member';

-- 2. Add candidate role to invitation_tokens table
ALTER TABLE invitation_tokens 
MODIFY COLUMN role ENUM('admin', 'board', 'head', 'member', 'alumni', 'candidate') 
NOT NULL DEFAULT 'member';

-- 3. Add image_path column to inventory table if missing
-- For inventory_items table:
ALTER TABLE inventory_items ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- OR for inventory table:
ALTER TABLE inventory ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;
```

## Testing

After running the migration, verify:

1. **User roles**: Check that the users and invitation_tokens tables have the correct ENUM:
```sql
SHOW COLUMNS FROM users LIKE 'role';
SHOW COLUMNS FROM invitation_tokens LIKE 'role';
-- Should show: enum('admin','board','head','member','alumni','candidate')
```

2. **Inventory table**: Check that image_path column exists:
```sql
-- For inventory_items table:
SHOW COLUMNS FROM inventory_items LIKE 'image_path';

-- OR for inventory table:
SHOW COLUMNS FROM inventory LIKE 'image_path';
-- Should show: image_path varchar(255) YES NULL
```

Expected migration output:
```
============================================================
Starting migration: Add Candidate Role and Fix Inventory Table
============================================================

PART 1: Adding 'candidate' role to user tables
------------------------------------------------------------
✓ Successfully added 'candidate' role to users table
✓ Successfully added 'candidate' role to invitation_tokens table

PART 2: Fixing inventory table structure
------------------------------------------------------------
Found 'inventory_items' table (full schema)
Checking and updating inventory_items table structure...
  - Adding missing column: image_path...
    ✓ Added image_path column
✓ inventory_items table structure updated successfully

============================================================
✓ Migration completed successfully!

Summary:
  - Added 'candidate' role to users table
  - Added 'candidate' role to invitation_tokens table
  - Fixed inventory_items table structure
  - Ensured image_path column exists
```
php tests/test_candidate_role_inventory_migration.php
```

Expected output:
```
======================================================================
Testing: Candidate Role and Inventory Migration
======================================================================

Testing User Database Changes...
----------------------------------------------------------------------

Testing Content Database Changes...
----------------------------------------------------------------------

======================================================================
Test Results
======================================================================

SUCCESSES (X):
  ✓ Users table has 'candidate' role in ENUM
  ✓ invitation_tokens table has 'candidate' role in ENUM
  ✓ Inventory table exists
  ✓ easyverein_id is INT UNSIGNED
  ...

======================================================================
✓ ALL TESTS PASSED
======================================================================
```

## Safety Features

The migration script is **idempotent** - it can be run multiple times safely:
- Checks if changes already exist before applying them
- Won't fail if run on an already-migrated database
- Automatically detects which inventory table schema is in use
- Provides clear output about what was changed

## Schema Design Notes

### Role Simplification
The new role structure uses 6 core roles:
- `admin`: Full system access
- `board`: Board member access
- `head`: Department head access
- `member`: Standard member access
- `alumni`: Alumni access
- `candidate`: Probationary/candidate member access (new)

### Inventory Table Flexibility
The migration supports both inventory table schemas:
- **inventory_items**: Used in full schema installations
- **inventory**: Used in modern/modular schema installations

The script automatically detects which table exists and applies the fix accordingly.

## Files Changed

### Migration Script
- `sql/migrate_add_candidate_role_fix_inventory.php` - Main migration logic

### Documentation
- `sql/MIGRATION_CANDIDATE_INVENTORY.md` - Migration documentation

## Rollback

⚠️ **Warning**: This migration modifies ENUMs. Rolling back requires careful consideration.

If you need to rollback:
1. Remove 'candidate' and 'head' from role ENUMs (but only if no users have these roles)
2. Remove image_path column from inventory table (but only if not in use)

It's safer to move forward with the migration rather than attempt rollback. Test on a staging environment first!

## Support

If you encounter issues:
1. Check the error message from the migration script
2. Verify database credentials in `.env` file
3. Check database user permissions (needs ALTER TABLE rights)
4. Ensure the correct inventory table exists (inventory or inventory_items)

## References

- Purpose: Fix 'Column not found' error for image_path column
- Role System: Added candidate role for probationary membership tracking
- Simplifies role structure to 6 core roles for easier management
