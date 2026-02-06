# Project and User Profile Update - Implementation Summary

## Overview
This implementation adds project type classification (Internal/External) and user notification preferences to the IBC Intranet system.

## Changes Made

### 1. Database Schema Updates

#### Projects Table (Content Database)
- Added `type` field: ENUM('internal', 'external') DEFAULT 'internal'
- Added index on `type` column for performance
- Updated schema file: `sql/content_database_schema.sql`

#### Users Table (User Database)
- Added `notify_new_projects`: BOOLEAN DEFAULT TRUE
- Added `notify_new_events`: BOOLEAN DEFAULT FALSE
- Updated schema file: `sql/user_database_schema.sql`

**Migration Script:** `sql/migrate_add_project_type_and_notifications.php`
**Documentation:** `sql/MIGRATION_INSTRUCTIONS.md`

### 2. Backend Changes

#### includes/models/Project.php
**New/Updated Methods:**
- `create()` - Now handles 'type' field and triggers notifications
- `update()` - Now handles 'type' field and triggers notifications when publishing
- `sendNewProjectNotifications()` - New private method that:
  - Fetches all users with `notify_new_projects = 1`
  - Builds HTML email using MailService template
  - Sends notifications to all subscribed users
  - Handles errors gracefully without failing project creation

**Notification Features:**
- Emails sent when a project is published (not draft)
- Email includes: project title, type, description, start date, and CTA button
- Uses IBC corporate email template
- Error handling to prevent notification failures from blocking project operations

#### includes/models/User.php
**New Method:**
- `updateNotificationPreferences()` - Updates user notification settings

### 3. Frontend Changes

#### pages/projects/manage.php (Project Edit/Create)
**Added:**
- "Projekt-Typ" dropdown with options: Intern, Extern
- Visual badge showing project type (Blue for Internal, Green for External)
- Type field saved to database on form submission

**Layout Changes:**
- Reorganized form to show Type alongside Priority
- Status dropdown now full-width when editing

#### pages/projects/index.php (Project List)
**Added:**
- Filter bar at top with three buttons:
  - "Alle" (All) - Shows all projects
  - "Intern" (Internal) - Shows only internal projects
  - "Extern" (External) - Shows only external projects
- Visual badges on each project card showing type
- URL parameter handling: `?type=all|internal|external`

**Styling:**
- Active filter button highlighted with primary color
- Internal projects: Blue badge with building icon
- External projects: Green badge with users icon

#### pages/projects/view.php (Project Detail)
**Added:**
- Type badge displayed alongside status and priority badges
- Consistent styling with other pages

#### pages/auth/profile.php (User Profile)
**Added:**
- New "Benachrichtigungen" (Notifications) section
- Two checkboxes:
  - "Neue Projekte" - Checked by default
  - "Neue Events" - Unchecked by default
- Save button to update preferences
- Success/error messages for feedback

**Layout:**
- Full-width card below 2FA settings
- Clear descriptions for each notification type
- Styled with consistent card design

### 4. User Experience Flow

#### Creating a Project (Manager/Board):
1. Navigate to Projects > Manage
2. Click "Neues Projekt"
3. Fill in project details including new "Projekt-Typ" dropdown
4. Choose to save as draft or publish
5. If published, subscribed users receive email notifications

#### Filtering Projects (All Users):
1. Navigate to Projects
2. See filter bar at top with "Alle", "Intern", "Extern" buttons
3. Click desired filter
4. Page reloads showing only matching projects
5. Each project card shows type badge

#### Managing Notifications (All Users):
1. Navigate to Profile
2. Scroll to "Benachrichtigungen" section
3. Check/uncheck notification preferences
4. Click "Benachrichtigungseinstellungen speichern"
5. See success message

### 5. Email Notification Details

**Trigger:** When a project status changes from 'draft' to any other status (publishing)

**Recipients:** All users where `notify_new_projects = 1`

**Email Content:**
- Subject: "Neues Projekt: [Project Title]"
- Professional HTML template with IBC branding
- Project information: Title, Type, Description (truncated), Start Date
- Call-to-action button linking to project detail page

**Error Handling:**
- Notification failures are logged but don't prevent project creation
- Each recipient error is handled individually
- Failed notifications don't affect other recipients

### 6. Default Values and Backward Compatibility

**Existing Projects:**
- Will have `type = 'internal'` by default
- No manual update required

**Existing Users:**
- Will have `notify_new_projects = TRUE` (opt-out model)
- Will have `notify_new_events = FALSE` (opt-in model)
- Users can change preferences in profile

**Backward Compatibility:**
- All changes are additive with safe defaults
- No existing functionality is broken
- Pages gracefully handle missing values

### 7. Security Considerations

**Notification System:**
- Only authenticated users in database receive notifications
- Email addresses validated before sending
- No sensitive project data (client details) included in emails

**Database Access:**
- Uses prepared statements for all queries
- Proper error handling prevents information leakage
- Type field properly escaped in all outputs

**Permission Checks:**
- Existing permission system maintained
- Only managers/board can create/edit projects
- All users can view their notification preferences

## Testing Recommendations

### Manual Testing:
1. Create a new internal project and verify email notifications
2. Create a new external project and verify email notifications
3. Filter projects by type on index page
4. Update notification preferences and verify they persist
5. Verify type badges appear on all project pages
6. Test with user who has notifications disabled

### Database Testing:
1. Run migration script on test database
2. Verify schema changes with DESCRIBE commands
3. Test rollback procedure

### Integration Testing:
1. Verify MailService integration
2. Test email delivery with multiple recipients
3. Test error handling when mail service fails

## Files Modified

### Database:
- `sql/content_database_schema.sql`
- `sql/user_database_schema.sql`
- `sql/migrate_add_project_type_and_notifications.php` (new)
- `sql/MIGRATION_INSTRUCTIONS.md` (new)

### Models:
- `includes/models/Project.php`
- `includes/models/User.php`

### Pages:
- `pages/projects/manage.php`
- `pages/projects/index.php`
- `pages/projects/view.php`
- `pages/auth/profile.php`

## Deployment Steps

1. **Backup databases** (both content and user databases)
2. **Run migration:** `php sql/migrate_add_project_type_and_notifications.php`
3. **Verify migration:** Check database schema
4. **Deploy code:** Push changes to production
5. **Test notifications:** Create a test project and verify emails
6. **Monitor logs:** Check for any errors in email sending

## Future Enhancements

Potential improvements for future iterations:
- Add type filter to project management page
- Create detailed notification preferences (per-type notifications)
- Add notification history/log for users
- Implement in-app notifications in addition to email
- Add notification frequency settings (immediate, daily digest, etc.)
- Export projects filtered by type
- Analytics dashboard showing project types distribution

## Support Information

**Migration Issues:**
- See `sql/MIGRATION_INSTRUCTIONS.md` for manual SQL commands
- Check database connection settings in config files
- Verify user has ALTER table permissions

**Email Notification Issues:**
- Check MailService configuration
- Verify SMTP settings
- Check error logs for specific failures
- Test with `src/MailService.php` test methods

**UI Issues:**
- Clear browser cache
- Verify CSS/JS files loaded
- Check browser console for errors
