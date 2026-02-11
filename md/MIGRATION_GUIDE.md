# Board Role Types Migration Guide

## Overview
This migration adds new board role types to the system to provide more granular control over board member permissions.

## New Role Types Added
1. **vorstand_intern** (Board Internal) - Internal board members with read-only access to invoices
2. **vorstand_extern** (Board External) - External board members with read-only access to invoices  
3. **vorstand_finanzen_recht** (Board Finance and Legal) - Board members with full invoice management including marking invoices as paid
4. **honorary_member** (Ehrenmitglied) - Honorary members with limited access

## Changes Made

### Database Schema
- Updated `users` table `role` column to include new board role types
- Updated `invitation_tokens` table `role` column to include new board role types
- Migration file: `sql/migration_add_board_role_types.sql`

### Permissions
- Only users with `vorstand_finanzen_recht` role can mark invoices as paid
- All board role types (`board`, `vorstand_intern`, `vorstand_extern`, `vorstand_finanzen_recht`) have access to:
  - Invoices (read access)
  - Users management
  - Settings
  - Statistics

### Sidebar Navigation
- Removed "Verwaltung" dropdown menu
- Moved "Benutzer", "Einstellungen", and "Statistiken" directly under "Rechnungen"
- Implemented role-based visibility for all menu items
- Updated sidebar footer to show user's full name above email

### UI Improvements
- Enhanced sidebar gradients and hover effects
- Improved card shadows and hover animations
- Better button styling with modern design

## How to Apply the Migration

### Option 1: Using the PHP Migration Runner (Recommended)
```bash
php run_migration.php
```

### Option 2: Manual SQL Execution
1. Connect to your MySQL database using your preferred client
2. Select the user database (dbs15253086)
3. Execute the SQL from `sql/migration_add_board_role_types.sql`

### Option 3: Using MySQL Command Line
```bash
mysql -h db5019508945.hosting-data.io -u dbu4494103 -p dbs15253086 < sql/migration_add_board_role_types.sql
```

## Post-Migration Steps

1. **Verify the migration:**
   ```sql
   SHOW COLUMNS FROM users LIKE 'role';
   ```
   The output should show the new role types in the ENUM definition.

2. **Update existing board users (if needed):**
   - Log in as an admin
   - Go to "Benutzer" page
   - Update board member roles to the appropriate new role types:
     - Assign `vorstand_finanzen_recht` to finance/legal board members
     - Assign `vorstand_intern` or `vorstand_extern` to other board members

3. **Test the changes:**
   - Log in with different role types
   - Verify sidebar visibility is correct for each role
   - Test invoice marking as paid (should only work for `vorstand_finanzen_recht`)

## Migration Safety

- The migration preserves existing data
- Existing 'board' role is kept as-is for backward compatibility
- No existing users will be automatically changed - manual updates required
- All changes are additive (no data deletion)

## Rollback (if needed)

If you need to rollback the migration:
```sql
ALTER TABLE users 
MODIFY COLUMN role ENUM('board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
NOT NULL DEFAULT 'member';

ALTER TABLE invitation_tokens 
MODIFY COLUMN role ENUM('board', 'head', 'member', 'alumni', 'candidate', 'alumni_board') 
NOT NULL DEFAULT 'member';
```

**Warning:** Rollback will fail if any users are assigned to the new role types. You must first reassign those users to the old 'board' role.

## Support

If you encounter any issues during migration:
1. Check the database connection settings in `.env`
2. Ensure your database user has ALTER TABLE privileges
3. Review MySQL error logs for detailed error messages
4. Contact support with the specific error message
