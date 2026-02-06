# SQL Schema Files

This directory contains SQL schema files for the offer management system.

## Fresh Installation Schemas

For a **fresh system installation**, use these comprehensive schema files:

### `full_user_schema.sql`
Complete user database schema including:
- **users**: User accounts with authentication (id, email, password, role, created_at, updated_at)
- **password_resets**: Password reset tokens (email, token, expires_at)

### `full_content_schema.sql`
Complete content database schema including all modules:

**Inventory Module:**
- `inventory_categories`: Item categories
- `inventory_locations`: Storage locations
- `inventory_items`: Items with stock tracking and EasyVerein integration
  - Includes: id, name, description, category_id, location_id, current_stock, min_stock, serial_number, unit, unit_price, image_path, notes, easyverein_id, last_synced_at, is_archived_in_easyverein
- `inventory_checkouts`: Item checkout tracking with reminder system
  - Includes: id, item_id, user_id, quantity, checkout_date, expected_return_date, returned_date, status, notes, last_reminder_sent_at

**Events Module:**
- `events`: Events with image_path, type, and status
- `event_registrations`: User event registrations

**Projects Module:**
- `projects`: Project management with client information
- `project_members`: Team member assignments
- `project_applications`: User applications to join projects
- `project_files`: Project file attachments

**Alumni Module:**
- `alumni_profiles`: Alumni profiles with job information and reminder tracking
  - Includes: user_id, first_name, last_name, email, mobile_phone, linkedin_url, xing_url, industry, company, position, image_path, last_reminder_sent_at

**Blog Module:**
- `blog_posts`: Blog posts with categorization
- `blog_comments`: Comments on posts
- `blog_likes`: User likes on posts

## Legacy Schema Files

### `user_database_schema.sql`
Original user database schema with additional features (2FA, sessions, invitations).

### `content_database_schema.sql`
Original content database schema with core tables.

## Migration Scripts

### `fix_schema_and_roles.php`
Migration script to finalize the database schema with the following fixes:

1. **Inventory Fix**: Checks if `inventory_items` table has `image_path` column and adds it if missing
2. **Role Fix**: Modifies the `users` table role ENUM to include 'candidate'
3. **Invitation Validity**: Checks if `user_invitations` table has `expires_at` (DATETIME) column and adds it if missing

**Usage:**
```bash
php sql/fix_schema_and_roles.php
```

**Output:**
The script prints status messages for each operation and completes with:
```
✅ Database Schema fixed and Roles updated
```

**Features:**
- Idempotent: Can be run multiple times safely
- Checks before making changes to avoid errors
- Clear status messages for each operation
- Handles missing tables gracefully

### `migrate_add_candidate_role.php`
Migration script to add the 'candidate' role to the users table.

**Changes Made:**
1. **Role ENUM Update**: Modifies the `users` table role ENUM to include: 'admin', 'board', 'head', 'member', 'alumni', 'candidate'
2. **Default Value**: Sets the default role to 'member'

**Usage:**
```bash
php sql/migrate_add_candidate_role.php
```

**Output:**
The script prints status messages and completes with:
```
✅ Rolle Anwärter (candidate) erfolgreich zur Datenbank hinzugefügt
```

**Testing:**
```bash
php tests/test_add_candidate_role_migration.php
```

**Features:**
- Idempotent: Can be run multiple times safely
- Checks if 'candidate' role already exists before making changes
- Clear status messages in German
- Uses exact ENUM value matching with quotes to prevent false positives

### `migrate_add_candidate_role_fix_inventory.php`
Legacy migration script for adding candidate role and fixing inventory table structure.

### `migrate_features_v2.php`
Migration script to add new features for security, notifications, and project types.

**Changes Made:**
1. **Security Features** (users table):
   - `failed_login_attempts` (INT, default 0): Track failed login attempts
   - `locked_until` (DATETIME, nullable): Temporary account lock timestamp
   - `is_locked_permanently` (BOOLEAN, default 0): Permanent lock flag

2. **Notification Preferences** (users table):
   - `notify_new_projects` (BOOLEAN, default 1): Enable project notifications by default
   - `notify_new_events` (BOOLEAN, default 0): Disable event notifications by default

3. **Project Types** (projects table):
   - `type` (ENUM('internal', 'external'), default 'internal'): Project type classification

**Usage:**
```bash
php sql/migrate_features_v2.php
```

**Testing:**
```bash
php tests/test_features_v2_migration.php
```

**Features:**
- Idempotent: Can be run multiple times safely
- Uses `Database::getUserDB()` for user table updates
- Uses `Database::getContentDB()` for project table updates
- Checks before making changes to avoid errors
- Clear status messages for each operation

### `remove_old_migrations.php`
Utility script for cleaning up obsolete migration artifacts.

The `migrations/` directory (if present) contains incremental database changes for existing installations.

## Usage

For a **fresh installation**:
```bash
# Import user schema
mysql -u username -p user_db < sql/full_user_schema.sql

# Import content schema
mysql -u username -p content_db < sql/full_content_schema.sql
```

For **existing installations**:
Use the migration files in the `migrations/` directory to update your schema incrementally.

## Notes

- All tables use `IF NOT EXISTS` to prevent errors if tables already exist
- Foreign keys reference `user_db.users` where appropriate (noted in comments)
- Default data for inventory categories and locations is included in `full_content_schema.sql`
- All schemas use utf8mb4 charset for proper international character support
