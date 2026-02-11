# Fix for /api/get_invitations.php 500 Error

## Problem Description

The `/api/get_invitations.php` API endpoint was returning a 500 Internal Server Error because it was trying to query and update database columns that didn't exist in the `invitation_tokens` table.

Specifically, the code was:
- Querying for `used_at IS NULL` to find unused invitation tokens
- Updating `used_at` and `used_by` when a token is used during registration

However, these columns were missing from the database schema.

## Solution

Added the missing `used_at` and `used_by` columns to the `invitation_tokens` table.

## Changes Made

### 1. Schema Update (`sql/dbs15253086.sql`)

Updated the `invitation_tokens` table definition to include:
- `used_at DATETIME DEFAULT NULL` - Records when the invitation token was used
- `used_by INT UNSIGNED DEFAULT NULL` - Records which user used the token
- Foreign key constraint `fk_invitation_created_by` - Links `created_by` to users table
- Foreign key constraint `fk_invitation_used_by` - Links `used_by` to users table
- Indexes on `used_at` and `used_by` for better query performance

### 2. Migration Script (`sql/migration_add_used_at_column.sql`)

Created a migration script that can be run on existing databases to add the missing columns without losing data.

## How to Apply the Fix

### For New Installations
Simply run the updated schema file:
```bash
mysql -u username -p < sql/dbs15253086.sql
```

### For Existing Databases
Run the migration script:
```bash
mysql -u username -p < sql/migration_add_used_at_column.sql
```

Or manually execute:
```sql
USE dbs15253086;

ALTER TABLE invitation_tokens 
ADD COLUMN used_at DATETIME DEFAULT NULL AFTER expires_at;

ALTER TABLE invitation_tokens 
ADD COLUMN used_by INT UNSIGNED DEFAULT NULL AFTER used_at;

ALTER TABLE invitation_tokens 
ADD INDEX idx_used_at (used_at);

ALTER TABLE invitation_tokens 
ADD INDEX idx_used_by (used_by);

ALTER TABLE invitation_tokens 
ADD CONSTRAINT fk_invitation_used_by
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE invitation_tokens 
ADD CONSTRAINT fk_invitation_created_by
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
```

## Verification

After applying the migration, verify the changes:

```sql
-- Check table structure
DESCRIBE invitation_tokens;

-- Check foreign keys
SHOW CREATE TABLE invitation_tokens;

-- Test the API endpoint
-- Navigate to your admin user management page and check if the invitations section loads without errors
```

## Related Features

This fix resolves the 500 error in the invitation management system. Note that this is separate from the "Meine offenen Ausleihen" (My open loans) feature, which is for inventory/rental management and was already working correctly.

## Files Modified
- `sql/dbs15253086.sql` - Updated schema definition
- `sql/migration_add_used_at_column.sql` - Created migration script

## No Code Changes Required
The PHP code (`api/get_invitations.php` and `pages/auth/register.php`) was already correctly implemented to use these columns. Only the database schema was missing them.
