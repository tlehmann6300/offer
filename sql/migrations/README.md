# Database Migrations

This directory contains database migration scripts for upgrading existing IBC Intranet installations.

## How to Use Migrations

### For New Installations
If you are setting up the system for the first time, **skip this directory** and use the main schema files:
- `sql/user_database_schema.sql`
- `sql/content_database_schema.sql`

### For Existing Installations
If you already have a running IBC Intranet system and want to upgrade to the latest version, run the migration scripts in order:

1. **Backup your databases first!**
   ```bash
   mysqldump -h <host> -u <user> -p dbs15253086 > backup_users_$(date +%Y%m%d).sql
   mysqldump -h <host> -u <user> -p dbs15161271 > backup_content_$(date +%Y%m%d).sql
   ```

2. **Run migrations in numerical order:**
   ```bash
   mysql -h <host> -u <user> -p < sql/migrations/001_add_alumni_roles_and_locations.sql
   mysql -h <host> -u <user> -p < sql/migrations/002_add_checkout_system.sql
   ```

## Available Migrations

### 001_add_alumni_roles_and_locations.sql
**Date:** 2026-02-01

**Changes:**
- Adds new locations: H-1.88 (Lagerraum H-1.88) and H-1.87 (Lagerraum H-1.87)
- Adds new user roles: `alumni` and `alumni_board`
- Adds `is_alumni_validated` field to users table for alumni approval workflow
- Updates invitation_tokens table to support new roles

**Breaking Changes:** None - fully backward compatible

**Required Actions After Migration:**
- None - all changes are automatic

### 002_add_checkout_system.sql
**Date:** 2026-02-01

**Changes:**
- Adds new locations: Furtwangen H-Bau -1.87 and Furtwangen H-Bau -1.88
- Creates `inventory_checkouts` table for tracking borrowed inventory items
- Extends `inventory_history.change_type` ENUM to include: `checkout`, `checkin`, `writeoff`
- Supports checkout/check-in workflow with defect tracking

**Breaking Changes:** None - fully backward compatible

**Required Actions After Migration:**
- None - all changes are automatic
- New features become available immediately in the UI

## Migration Best Practices

1. Always backup your databases before running migrations
2. Test migrations on a staging environment first
3. Run migrations during low-traffic periods
4. Keep track of which migrations have been applied
5. Never modify applied migrations - create new ones instead

## Troubleshooting

### Error: "Column already exists"
Some migrations use `IF NOT EXISTS` or `ON DUPLICATE KEY UPDATE` to be idempotent. If you see errors about existing columns/data, the migration may have already been partially applied. Check the verification section at the end of the migration script.

### Error: "Cannot change column type with existing data"
This can happen when modifying ENUM columns with invalid values. The migration scripts handle this by creating temporary columns, copying data, and swapping columns.

## Support

For migration issues, check:
1. The VERIFICATION section at the end of each migration script
2. MySQL error logs
3. System logs in the admin panel (Admin → Audit-Logs)
