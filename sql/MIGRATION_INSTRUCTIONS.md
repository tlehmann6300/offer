# Database Migration Instructions

## Migration: Add Project Type and Notification Preferences

This migration adds the following fields to the database:

### Projects Table (Content Database)
- **type**: ENUM('internal', 'external') DEFAULT 'internal'
  - Allows projects to be classified as internal or external
  - Index added for performance

### Users Table (User Database)
- **notify_new_projects**: BOOLEAN DEFAULT TRUE
  - Controls whether users receive email notifications for new projects
  - Set to TRUE by default (opt-out model)
  
- **notify_new_events**: BOOLEAN DEFAULT FALSE
  - Controls whether users receive email notifications for new events
  - Set to FALSE by default (opt-in model)

## How to Run the Migration

### Option 1: Run the migration script
```bash
php sql/migrate_add_project_type_and_notifications.php
```

### Option 2: Manual SQL execution

If you need to run the migration manually, execute these SQL statements:

**Content Database:**
```sql
ALTER TABLE projects 
ADD COLUMN type ENUM('internal', 'external') NOT NULL DEFAULT 'internal' 
AFTER priority;

ALTER TABLE projects 
ADD INDEX idx_type (type);
```

**User Database:**
```sql
ALTER TABLE users 
ADD COLUMN notify_new_projects BOOLEAN NOT NULL DEFAULT TRUE 
AFTER tfa_enabled;

ALTER TABLE users 
ADD COLUMN notify_new_events BOOLEAN NOT NULL DEFAULT FALSE 
AFTER notify_new_projects;
```

## Verification

After running the migration, verify the changes:

**Content Database:**
```sql
DESCRIBE projects;
-- Should show 'type' column with ENUM('internal', 'external')

SHOW INDEX FROM projects WHERE Key_name = 'idx_type';
-- Should show index on 'type' column
```

**User Database:**
```sql
DESCRIBE users;
-- Should show 'notify_new_projects' and 'notify_new_events' columns
```

## Rollback (if needed)

If you need to rollback these changes:

**Content Database:**
```sql
ALTER TABLE projects DROP INDEX idx_type;
ALTER TABLE projects DROP COLUMN type;
```

**User Database:**
```sql
ALTER TABLE users DROP COLUMN notify_new_events;
ALTER TABLE users DROP COLUMN notify_new_projects;
```

## Impact

### Features Added:
1. Projects can now be classified as "Internal" or "External"
2. Filter bar on projects page to filter by type
3. Visual badges showing project type (Blue for Internal, Green for External)
4. Users can control email notifications for new projects and events
5. Email notifications sent to subscribed users when new projects are published

### Breaking Changes:
- None. All changes are backward compatible with default values.

### Default Behavior:
- Existing projects will be set to 'internal' type
- Existing users will have notify_new_projects=TRUE (they will receive notifications)
- Existing users will have notify_new_events=FALSE (they won't receive notifications)
