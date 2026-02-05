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

## Migration Files

The `migrations/` directory contains incremental database changes for existing installations.

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
