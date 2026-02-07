# Database Migration Documentation

## Overview
This directory contains consolidated migration scripts for the production databases. All migrations have been collected from individual migration files and organized into database-specific SQL files.

## Files Created

### 1. `dbs15253086.sql` - User Database Migrations
Consolidated SQL file containing all migrations for the User Database (dbs15253086).

**Migrations included:**
- Add 'candidate' and 'alumni_board' roles to users table
- Add 'candidate' and 'alumni_board' roles to user_invitations table
- Add security features (failed_login_attempts, locked_until, is_locked_permanently)
- Add notification preferences (notify_new_projects, notify_new_events)
- Add expires_at column to user_invitations table
- Create invoices table for invoice management

### 2. `dbs15161271.sql` - Content Database Migrations
Consolidated SQL file containing all migrations for the Content Database (dbs15161271).

**Migrations included:**
- Add image_path column to inventory_items table
- Add project type to projects table (internal/external)
- Add German profile fields (studiengang, semester, angestrebter_abschluss)
- Add English student fields (study_program, degree, graduation_year)
- Add about_me field to alumni_profiles
- Make company and position nullable for candidates/members

### 3. `apply_all_migrations_to_production.php`
PHP script to automatically apply all migrations from the SQL files to the production databases.

**Features:**
- Applies migrations from both SQL files
- Provides detailed progress output
- Handles duplicate column errors gracefully
- Shows summary of applied migrations

### 4. `drop_and_clean_databases.php`
⚠️ **DESTRUCTIVE SCRIPT** - Use with extreme caution!

PHP script to drop all tables in both databases and clean server files.

**Safety features:**
- Requires explicit confirmation: "DROP ALL DATABASES"
- Works in both CLI and web environments
- Provides detailed output of all operations
- Shows summary of dropped tables and deleted files

## Usage Instructions

### Applying Migrations to Production

**Method 1: Using the PHP script (Recommended)**
```bash
php sql/apply_all_migrations_to_production.php
```

**Method 2: Manual SQL import**
```bash
# User Database
mysql -h db5019508945.hosting-data.io \
      -u dbu4494103 \
      -p \
      dbs15253086 < sql/dbs15253086.sql

# Content Database
mysql -h db5019375140.hosting-data.io \
      -u dbu2067984 \
      -p \
      dbs15161271 < sql/dbs15161271.sql
```

### Dropping Databases and Cleaning Server

⚠️ **WARNING: This will permanently delete ALL data!**

**Command line:**
```bash
php sql/drop_and_clean_databases.php
```
Then type: `DROP ALL DATABASES` when prompted.

**Web browser:**
Navigate to: `https://your-domain.com/sql/drop_and_clean_databases.php`
Then type: `DROP ALL DATABASES` in the confirmation form.

## Post-Migration Steps

After applying migrations:

1. **Verify database schema:**
   ```bash
   php verify_db_schema.php
   ```

2. **Test application functionality:**
   - Login/logout
   - User registration
   - Profile management
   - Projects
   - Events
   - Inventory
   - Invoices

3. **Delete migration scripts for security:**
   ```bash
   rm sql/apply_all_migrations_to_production.php
   rm sql/drop_and_clean_databases.php
   ```

## Fresh Installation

If you dropped the databases and want to start fresh:

1. **Apply full schemas:**
   ```bash
   mysql -h db5019508945.hosting-data.io \
         -u dbu4494103 \
         -p \
         dbs15253086 < sql/full_user_schema.sql

   mysql -h db5019375140.hosting-data.io \
         -u dbu2067984 \
         -p \
         dbs15161271 < sql/full_content_schema.sql
   ```

2. **Setup admin user:**
   ```bash
   php setup_admin.php
   ```

## Migration Details

### User Database (dbs15253086) Changes

| Migration | Description | Impact |
|-----------|-------------|--------|
| Candidate Role | Adds 'candidate' role to users and invitations | Allows prospective members to register |
| Alumni Board Role | Adds 'alumni_board' role | Allows alumni board members with special permissions |
| Security Features | Adds login tracking and account locking | Enhances security with failed login protection |
| Notification Preferences | Adds email notification toggles | Gives users control over notifications |
| Invoices Table | Creates invoice management system | Enables receipt tracking and approval workflow |

### Content Database (dbs15161271) Changes

| Migration | Description | Impact |
|-----------|-------------|--------|
| Project Types | Adds internal/external classification | Allows filtering projects by type |
| Profile Fields | Adds student and career fields | Supports profiles for all user types |
| Image Path | Adds image support to inventory | Allows visual inventory management |

## Troubleshooting

### Common Issues

**Issue:** "Duplicate column name" error
- **Solution:** This is normal. The migration scripts are idempotent and will skip already-applied changes.

**Issue:** "Table doesn't exist" error
- **Solution:** Ensure you're running migrations on the correct database. Check database connection settings in `.env`.

**Issue:** "Connection failed" error
- **Solution:** Verify database credentials in `.env` file and check network connectivity.

## Security Considerations

1. **Delete migration scripts after use:**
   - `apply_all_migrations_to_production.php`
   - `drop_and_clean_databases.php`

2. **Keep SQL files for reference:**
   - `dbs15253086.sql`
   - `dbs15161271.sql`

3. **Backup before migrations:**
   ```bash
   # Backup User Database
   mysqldump -h db5019508945.hosting-data.io -u dbu4494103 -p dbs15253086 > backup_user_$(date +%Y%m%d).sql
   
   # Backup Content Database
   mysqldump -h db5019375140.hosting-data.io -u dbu2067984 -p dbs15161271 > backup_content_$(date +%Y%m%d).sql
   ```

## Support

For issues or questions about migrations:
1. Check the migration output for specific error messages
2. Review the individual migration files in `sql/migrate_*.php`
3. Verify database schema with `verify_db_schema.php`
4. Check application logs for runtime errors

## Version History

- **2024-02-07**: Initial consolidated migration files created
  - Combined all migrations from individual PHP scripts
  - Created automated application script
  - Added database drop and cleanup script
