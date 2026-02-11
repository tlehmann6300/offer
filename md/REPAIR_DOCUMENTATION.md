# System Repair: Role Logic, Database Queries, and Schema Fixes

## Overview
This document describes all changes made to fix role logic, database queries, and schema mismatches across the application.

## Summary of Changes

### Part 1: Permissions & UI Fixes

#### 1.1 Role-Based Access Control
- **Updated**: All `hasRole('admin')` checks now use `AuthHandler::isAdmin()` helper function
- **Location**: 
  - `includes/templates/main_layout.php`
  - `pages/inventory/index.php`
  - `pages/inventory/sync.php`
- **Benefit**: The `isAdmin()` helper returns `true` for both 'admin' AND 'board' roles, ensuring consistent access control

#### 1.2 UI Text Updates
- **Changed**: "Meine offenen Aufgaben" → "Meine offenen Ausleihen"
- **Location**: `pages/dashboard/index.php` line 244
- **Benefit**: More accurate terminology for inventory rentals

#### 1.3 Role Translation
- **Status**: Already correctly implemented in `pages/admin/users.php`
- **Mapping**: 
  - board → Vorstand
  - head → Ressortleiter
  - member → Mitglied
  - alumni → Alumni

### Part 2: Database Model Fixes (Critical)

#### 2.1 BlogPost Model (`includes/models/BlogPost.php`)
**Issue**: Query attempted to SELECT `p.external_link` column that doesn't exist in all databases

**Fix**: Removed `p.external_link` from SELECT queries in:
- `getAll()` method (line 28)
- `getById()` method (line 94)

**Note**: Added clarifying comments explaining that external_link is omitted until migration is run

#### 2.2 Invoice Model (`includes/models/Invoice.php`)
**Issue**: Query used incorrect column name `i.reason` instead of `i.rejection_reason`

**Fix**: Replaced `i.reason` with `i.rejection_reason` in:
- `getAll()` method (line 268)
- `getAll()` method for head role (line 304)
- `getById()` method (line 435)
- `updateStatus()` method (line 347)

#### 2.3 Member Model (`includes/models/Member.php`)
**Issue**: Used cross-database JOIN that fails when user lacks cross-db permissions

**Fix**: Implemented 2-query approach:
1. Query Content DB for alumni profiles
2. Collect user_ids
3. Query User DB separately for user data
4. Merge results in PHP

**Security Enhancement**: Added proper parameter binding for role list to prevent SQL injection

### Part 3: Inventory Sync Fixes (`includes/services/EasyVereinSync.php`)

**Issue**: Referenced incorrect table name 'inventory' instead of 'inventory_items'

**Fixes Applied**:
1. Line 173: Changed SELECT query table name
2. Line 199: Changed UPDATE query table name  
3. Line 228: Changed INSERT query table name
4. Line 273: Changed SELECT query for archival
5. Line 285: Changed SELECT query for archival (empty result case)
6. Line 296: Changed UPDATE query for archival

**Column Updates**:
- Changed `current_stock` references to `quantity` (lines 173, 184, 209)

### Part 4: SQL Schema Updates

#### 4.1 Content Database Schema (`sql/dbs15161271.sql`)
**Status**: Complete and definitive schema with all required columns:
- `external_link VARCHAR(255) DEFAULT NULL` column in `blog_posts` table
- `angestrebter_abschluss VARCHAR(255) DEFAULT NULL` column in `alumni_profiles` table

#### 4.2 Invoice Database Schema (`sql/dbs15251284.sql`)
**Status**: Complete and definitive schema with all required columns:
- `updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` column in `invoices` table
- `rejection_reason TEXT DEFAULT NULL` column in `invoices` table

#### 4.3 User Database Schema (`sql/dbs15253086.sql`)
**Status**: Complete and definitive schema with all required columns

**Note**: All schema changes have been integrated into the definitive database schema files. The migration script (`migration_fix_schemas.sql`) has been removed as it's no longer needed.

## How to Apply Changes

### For Development/Testing Environments

1. **Pull the latest code** from the PR branch:
   ```bash
   git checkout copilot/fix-role-logic-database-queries
   git pull
   ```

2. **For new installations**, use the definitive schema files:
   ```bash
   # For Content DB (dbs15161271)
   mysql -u [username] -p dbs15161271 < sql/dbs15161271.sql
   
   # For Invoice DB (dbs15251284)
   mysql -u [username] -p dbs15251284 < sql/dbs15251284.sql
   
   # For User DB (dbs15253086)
   mysql -u [username] -p dbs15253086 < sql/dbs15253086.sql
   ```

3. **Verify the changes**:
   - Test admin/board user access to 'Verwaltung' menu
   - Test inventory sync functionality
   - Test invoice listing
   - Test blog post display with external links
   - Test member directory

### For Production Environment

1. **Backup your databases** before applying changes:
   ```bash
   mysqldump -u [username] -p dbs15161271 > backup_content_$(date +%Y%m%d).sql
   mysqldump -u [username] -p dbs15251284 > backup_invoice_$(date +%Y%m%d).sql
   mysqldump -u [username] -p dbs15253086 > backup_user_$(date +%Y%m%d).sql
   ```

2. **If you have existing databases**, manually add any missing columns as needed

3. **For new production installations**, use the definitive schema files during setup

4. **Deploy the updated code**

5. **Test all affected functionality**

## Testing Checklist

- [ ] Admin user can access 'Verwaltung' dropdown menu
- [ ] Board user can access 'Verwaltung' dropdown menu
- [ ] EasyVerein sync runs without errors
- [ ] Blog posts display correctly without crashes
- [ ] Invoice listing shows rejection_reason correctly
- [ ] Member directory loads without cross-db errors
- [ ] Dashboard shows "Meine offenen Ausleihen" instead of "Meine offenen Aufgaben"

## Rollback Plan

If issues occur, you can rollback:

1. **Code**: Revert to previous commit
   ```bash
   git checkout main
   ```

2. **Database**: Restore from backup
   ```bash
   mysql -u [username] -p dbs15161271 < backup_content_[date].sql
   mysql -u [username] -p dbs15251284 < backup_invoice_[date].sql
   ```

## Security Improvements

- Fixed potential SQL injection in Member model by using parameter binding
- Maintained proper prepared statement usage throughout
- No new security vulnerabilities introduced (verified by CodeQL)

## Notes for Developers

- The `external_link` column is defined in the schema but not queried to maintain backward compatibility with databases that haven't been migrated yet
- Once all databases are migrated, the `external_link` column can be added back to queries if needed
- The `isAdmin()` helper simplifies role checks and should be used instead of checking 'admin' or 'board' roles separately
- The 2-query approach in Member model is safer than cross-db JOINs and works better with restricted database permissions

## Support

If you encounter any issues with these changes, please:
1. Check the error logs for specific error messages
2. Verify the migration script ran successfully
3. Ensure database permissions are correct
4. Contact the development team with error details
