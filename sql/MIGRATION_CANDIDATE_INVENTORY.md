# Candidate Role and Inventory Migration

## Overview
This migration adds the "candidate" (Anwärter) role to the user system and fixes the inventory table structure to properly support easyVerein integration.

## What This Migration Does

### 1. User Database Changes
- **users table**: Adds 'candidate' to the role ENUM
- **invitation_tokens table**: Adds 'candidate' to the role ENUM

The complete role list becomes:
- `admin` - System administrators
- `board` - Board members
- `alumni_board` - Alumni board members
- `manager` - Managers
- `member` - Regular members
- `alumni` - Alumni
- `candidate` - Candidates (Anwärter) ⭐ NEW

### 2. Content Database Changes - Inventory Table

#### Column Updates
- **easyverein_id**: Changed from `VARCHAR(100)` to `INT UNSIGNED NULL UNIQUE`
  - Now nullable to support both easyVerein-synced items and manual entries
  - Unique constraint ensures no duplicate easyVerein IDs
- **name**: Expanded from `VARCHAR(100)` to `VARCHAR(255)`
- **location**: Added as `VARCHAR(255)` (text location from easyVerein)
  - Works alongside `location_id` (reference to locations table)
  - Allows flexible location storage from easyVerein sync
- **image_path**: Ensured exists as `VARCHAR(255)`
- **acquisition_date**: Added as `DATE` (from easyVerein)
- **value**: Added as `DECIMAL(10,2)` (item value from easyVerein)
- **last_synced_at**: Updated to `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`

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
MODIFY COLUMN role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni', 'candidate') 
NOT NULL DEFAULT 'member';

-- 2. Add candidate role to invitation_tokens table
ALTER TABLE invitation_tokens 
MODIFY COLUMN role ENUM('admin', 'board', 'alumni_board', 'manager', 'member', 'alumni', 'candidate') 
NOT NULL DEFAULT 'member';

-- 3. Fix inventory table (see full SQL in problem statement)
-- Note: The migration script handles this more safely with checks
```

## Testing

Run the test script to verify the migration was successful:
```bash
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
- Provides clear output about what was changed

## Schema Design Notes

### Why Two Location Columns?
The inventory table has both `location_id` and `location`:
- **location_id**: Foreign key reference to the `locations` table for internal location management
- **location**: Text field synced from easyVerein for flexible location descriptions

This dual approach supports:
1. Manual inventory entries using structured locations (`location_id`)
2. easyVerein synced items with arbitrary location text (`location`)

### Why Nullable easyverein_id?
The `easyverein_id` is nullable to support:
1. Items synced from easyVerein (have an ID)
2. Manually added items (no easyVerein ID)

Items with an `easyverein_id` are protected from manual editing to preserve sync integrity.

## Files Changed

### Migration Script
- `sql/migrate_add_candidate_role_fix_inventory.php` - Main migration logic

### Schema Files
- `sql/user_database_schema.sql` - Updated user role ENUMs
- `sql/full_user_schema.sql` - Updated full user schema
- `sql/content_database_schema.sql` - Updated inventory table structure

### Deployment
- `deploy_migrations.php` - Added migration to execution list

### Tests
- `tests/test_candidate_role_inventory_migration.php` - Comprehensive validation

## Rollback

⚠️ **Warning**: This migration modifies ENUMs and column types. Rolling back requires careful consideration.

If you need to rollback:
1. Remove 'candidate' from role ENUMs (but only if no users have this role)
2. Revert inventory column changes (but data may be lost)

It's safer to move forward with the migration rather than attempt rollback. Test on a staging environment first!

## Support

If you encounter issues:
1. Check the error message from the migration script
2. Run the test script to see which checks fail
3. Verify database credentials in `.env` file
4. Check database user permissions (needs ALTER TABLE rights)

## References

- Problem Statement: See original SQL code in issue description
- easyVerein Integration: Inventory sync with easyVerein requires specific column structure
- Role System: Candidate role allows for probationary membership tracking
