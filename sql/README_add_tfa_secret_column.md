# Add tfa_secret Column Migration

## Overview
This migration adds the missing `tfa_secret` column to the `users` table in the user database (dbs15253086).

## Problem
The application code references a `tfa_secret` column in the users table for storing two-factor authentication secrets, but this column was missing from the database schema, causing a `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'tfa_secret'` error.

## Solution
Add the `tfa_secret` column to the users table with the following specifications:
- Type: VARCHAR(255)
- Default: NULL
- Purpose: Store Google Authenticator secrets for 2FA functionality

## How to Apply

### For Production
Run the migration script on the production database:

```bash
mysql -u username -p dbs15253086 < sql/add_tfa_secret_column.sql
```

### For New Installations
The main schema file `sql/dbs15253086.sql` has been updated to include the `tfa_secret` column, so new installations will automatically have this column.

## Verification
After running the migration, verify the column exists:

```sql
DESCRIBE users;
```

You should see `tfa_secret` listed among the columns with type VARCHAR(255) and NULL as the default.

## Rollback
If you need to rollback this migration:

```sql
ALTER TABLE users DROP COLUMN tfa_secret;
```

## Related Files
- `/includes/models/User.php` - Contains `enable2FA()` and `disable2FA()` methods
- `/pages/auth/profile.php` - 2FA setup interface
- `/pages/auth/login.php` - 2FA verification during login
- `/src/Auth.php` - Authentication logic including 2FA verification
