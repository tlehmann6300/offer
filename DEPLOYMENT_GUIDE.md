# Deployment Guide: Database Migrations and Management

This guide explains how to apply database migrations to production and manage the production databases.

## Overview

The system uses two production databases:
- **User Database**: Configured in `.env` as `DB_USER_NAME` (default: `dbs15253086`)
  - Stores user accounts, authentication, and invoices
- **Content Database**: Configured in `.env` as `DB_CONTENT_NAME` (default: `dbs15161271`)
  - Stores projects, inventory, events, and alumni profiles

**Note**: Database connection details are stored in the `.env` file and should never be hard-coded in scripts or documentation.

All migrations have been consolidated into SQL files named after each database for easy deployment.

## Files and Scripts

### Consolidated Migration Files

1. **`sql/dbs15253086.sql`** - User Database Migrations
   - Adds 'candidate' and 'alumni_board' roles
   - Adds security features (login attempts tracking, account locking)
   - Adds notification preferences
   - Creates invoices table

2. **`sql/dbs15161271.sql`** - Content Database Migrations
   - Adds image_path to inventory_items
   - Adds project type classification
   - Adds profile fields for candidates/members/alumni
   - Makes company and position optional for non-alumni

### Deployment Scripts

1. **`sql/apply_all_migrations_to_production.php`**
   - Applies all consolidated migrations to production databases
   - Safe to run multiple times (idempotent)
   - Shows detailed progress and error messages
   - Exits with error code 1 if any fatal errors occur

2. **`sql/drop_and_clean_databases.php`**
   - ⚠️ **DANGEROUS** - Drops all tables in both databases
   - Requires confirmation before proceeding
   - Also cleans uploaded files from the server
   - Use only when resetting the production environment

## Step-by-Step Deployment Process

### Option 1: Apply Migrations to Existing Production System

This is the **recommended approach** for production systems with existing data.

1. **Backup the databases** (CRITICAL!)
   ```bash
   # Load environment variables from .env
   source .env
   
   # Backup user database
   mysqldump -h ${DB_USER_HOST} -u ${DB_USER_USER} -p ${DB_USER_NAME} > backup_user_db_$(date +%Y%m%d_%H%M%S).sql
   
   # Backup content database
   mysqldump -h ${DB_CONTENT_HOST} -u ${DB_CONTENT_USER} -p ${DB_CONTENT_NAME} > backup_content_db_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Apply the migrations**
   ```bash
   php sql/apply_all_migrations_to_production.php
   ```

3. **Verify the migration**
   - Check the output for any errors
   - All errors related to "Duplicate column" or "already exists" are normal (idempotent operations)
   - Fatal errors will cause the script to exit with code 1

4. **Test the application**
   - Verify that all features work correctly
   - Check that new fields are available in the UI
   - Test role-based access for 'candidate' and 'alumni_board' roles

### Option 2: Clean Install (Drop and Recreate)

⚠️ **WARNING**: This approach **deletes all data**. Only use for:
- Development/staging environments
- Completely resetting production (with proper backups)
- Initial setup of a new environment

1. **Backup everything** (if you have data you might need later)
   ```bash
   # Load environment variables from .env
   source .env
   
   # Backup databases
   mysqldump -h ${DB_USER_HOST} -u ${DB_USER_USER} -p ${DB_USER_NAME} > backup_user_db_$(date +%Y%m%d_%H%M%S).sql
   mysqldump -h ${DB_CONTENT_HOST} -u ${DB_CONTENT_USER} -p ${DB_CONTENT_NAME} > backup_content_db_$(date +%Y%m%d_%H%M%S).sql
   
   # Backup uploads directory
   tar -czf backup_uploads_$(date +%Y%m%d_%H%M%S).tar.gz uploads/
   ```

2. **Drop all tables and clean server**
   ```bash
   php sql/drop_and_clean_databases.php
   ```
   
   When prompted, type exactly: `DROP ALL DATABASES`

3. **Install fresh schema**
   ```bash
   # Load environment variables from .env
   source .env
   
   # Import user database schema
   mysql -h ${DB_USER_HOST} -u ${DB_USER_USER} -p ${DB_USER_NAME} < sql/full_user_schema.sql
   
   # Import content database schema
   mysql -h ${DB_CONTENT_HOST} -u ${DB_CONTENT_USER} -p ${DB_CONTENT_NAME} < sql/full_content_schema.sql
   ```

4. **Apply all migrations** (to ensure everything is up-to-date)
   ```bash
   php sql/apply_all_migrations_to_production.php
   ```

5. **Create admin user**
   ```bash
   php setup_admin.php
   ```

## Migration Details

### User Database (dbs15253086) Migrations

#### Migration 1: Role Extensions
- Adds 'candidate' role for prospective members
- Adds 'alumni_board' role for alumni board members
- Updates both `users` and `user_invitations` tables

#### Migration 2: Security Features
- `failed_login_attempts` (INT): Tracks failed login attempts
- `locked_until` (DATETIME): Temporary account lock until specific time
- `is_locked_permanently` (BOOLEAN): Permanent account ban flag

#### Migration 3: Notification Preferences
- `notify_new_projects` (BOOLEAN, default TRUE): Project notifications (opt-out model)
- `notify_new_events` (BOOLEAN, default FALSE): Event notifications (opt-in model)

#### Migration 4: Invitation Expiration
- `expires_at` (DATETIME): Expiration timestamp for invitation tokens

#### Migration 5: Invoice Management
- Creates `invoices` table for receipt tracking and approval workflow
- Includes status tracking: pending, approved, rejected, paid

### Content Database (dbs15161271) Migrations

#### Migration 1: Inventory Images
- Adds `image_path` column to `inventory_items` for item photos

#### Migration 2: Project Classification
- Adds `type` (ENUM: 'internal', 'external') to projects table
- Adds index for performance

#### Migration 3-4: Profile Fields for Candidates/Members
German fields:
- `studiengang` (VARCHAR 255): Field of study
- `semester` (VARCHAR 50): Current semester
- `angestrebter_abschluss` (VARCHAR 255): Desired degree

English fields:
- `study_program` (VARCHAR 100): Study program
- `degree` (VARCHAR 50): Degree type (B.Sc., M.Sc., etc.)
- `graduation_year` (INT): Year of graduation

#### Migration 5: Personal Bio
- `about_me` (TEXT): Personal description for all user roles

#### Migration 6: Optional Job Fields
- Makes `company` and `position` nullable for candidates/members
- Alumni still should fill these fields, but candidates may not have jobs yet

## Verification

After applying migrations, verify the changes:

```bash
php verify_db_schema.php
```

This script checks:
- All expected tables exist
- All expected columns exist
- Data types are correct
- Indexes are in place

## Rollback Procedure

If migrations cause issues:

1. **Stop the web server** (if applicable)
   ```bash
   sudo systemctl stop apache2
   # or
   sudo systemctl stop nginx
   ```

2. **Restore from backup**
   ```bash
   # Load environment variables from .env
   source .env
   
   # Restore user database
   mysql -h ${DB_USER_HOST} -u ${DB_USER_USER} -p ${DB_USER_NAME} < backup_user_db_TIMESTAMP.sql
   
   # Restore content database
   mysql -h ${DB_CONTENT_HOST} -u ${DB_CONTENT_USER} -p ${DB_CONTENT_NAME} < backup_content_db_TIMESTAMP.sql
   ```

3. **Restore uploaded files** (if they were cleaned)
   ```bash
   tar -xzf backup_uploads_TIMESTAMP.tar.gz
   ```

4. **Restart web server**
   ```bash
   sudo systemctl start apache2
   # or
   sudo systemctl start nginx
   ```

## Security Notes

1. **Delete deployment scripts** after successful deployment:
   ```bash
   rm sql/apply_all_migrations_to_production.php
   rm sql/drop_and_clean_databases.php
   rm deploy_migrations.php
   ```

2. **Restrict database access** to only necessary IPs

3. **Review .env file** to ensure credentials are secure

4. **Enable HTTPS** for all production traffic

5. **Set proper file permissions**:
   ```bash
   chmod 750 uploads/
   chmod 640 .env
   chmod 644 sql/*.sql
   ```

## Troubleshooting

### Error: "Duplicate column name"
- **Not an error**: This is expected when migrations are idempotent
- The script will skip already-applied changes

### Error: "Table doesn't exist"
- Check that you're running migrations on the correct database
- Verify database connection settings in `.env`
- Ensure fresh schema was imported first (for clean installs)

### Error: "Foreign key constraint fails"
- Ensure user database migrations are applied before content database
- Check that referenced data exists (e.g., users table has data)

### Error: "Access denied"
- Verify database credentials in `.env`
- Check that database user has ALTER, CREATE, and DROP privileges

## Support

For issues or questions:
- Check the migration scripts in `sql/` directory
- Review individual migration files: `sql/migrate_*.php`
- Consult the README files in the `sql/` directory

## Individual Migration Files (Reference Only)

These files have been consolidated into `dbs15253086.sql` and `dbs15161271.sql`:
- `migrate_add_candidate_role.php`
- `migrate_add_candidate_role_fix_inventory.php`
- `migrate_add_profile_fields.php`
- `migrate_add_project_type_and_notifications.php`
- `migrate_add_student_fields.php`
- `migrate_features_v2.php`
- `migrate_invoice_module.php`

**Do not run these individual scripts.** Use the consolidated SQL files instead.
