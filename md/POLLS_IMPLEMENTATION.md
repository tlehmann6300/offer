# Polling System Implementation

## Overview
This implementation adds a complete polling/survey system to the IBC Intranet, allowing board members and heads to create polls and members to vote on them.

## Files Created/Modified

### New Files
1. **sql/migration_polls.sql** - Database schema for polls system
2. **pages/polls/index.php** - List all active polls
3. **pages/polls/create.php** - Create new poll (head/board only)
4. **pages/polls/view.php** - View poll and vote/see results
5. **run_polls_migration.php** - Migration script to create tables

### Modified Files
1. **src/Auth.php** - Added 'polls' to canAccessPage() permissions
2. **includes/templates/main_layout.php** - Added polls navigation link

## Installation Instructions

### Step 1: Run Database Migration
Execute the migration script on your production server:

```bash
php run_polls_migration.php
```

Or manually run the SQL file on the Content Database:
```bash
mysql -h [DB_CONTENT_HOST] -u [DB_CONTENT_USER] -p [DB_CONTENT_NAME] < sql/migration_polls.sql
```

### Step 2: Verify Installation
1. Log in to the intranet
2. Check that "Umfragen" appears in the navigation menu
3. Board/Head users should see "Umfrage erstellen" button

## Features

### User Roles and Permissions
- **View Polls**: All authenticated users (filtered by target groups)
- **Create Polls**: head, board, vorstand_intern, vorstand_extern, vorstand_finanzen_recht
- **Vote**: Users whose role matches poll's target groups
- **One vote per user per poll**: Enforced by database constraint

### Poll Creation
Creators can:
- Set title and description
- Set end date/time
- Add multiple answer options (minimum 2)
- Select target groups (candidate, alumni_board, board, member, head)
- Add/remove answer options dynamically

### Voting
- Users see voting form if they haven't voted yet
- Radio button interface for single-choice voting
- Once voted, users can only see results
- Cannot change vote after submission

### Results Display
- Real-time vote counts and percentages
- Visual progress bars for each option
- Highlights user's selected option
- Shows total participation count

## Database Schema

### polls
- `id` - Primary key
- `title` - Poll title (VARCHAR 255)
- `description` - Optional description (TEXT)
- `created_by` - User ID of creator
- `start_date` - When poll was created
- `end_date` - When poll expires
- `target_groups` - JSON array of allowed roles
- `is_active` - Active status flag
- `created_at` - Timestamp

### poll_options
- `id` - Primary key
- `poll_id` - Foreign key to polls
- `option_text` - Answer option text (VARCHAR 255)

### poll_votes
- `id` - Primary key
- `poll_id` - Foreign key to polls
- `option_id` - Foreign key to poll_options
- `user_id` - User who voted
- `voted_at` - Vote timestamp
- **UNIQUE constraint** on (poll_id, user_id) prevents duplicate voting

## Security Features

1. **Authentication**: All pages require login
2. **Role-based access**: Create poll restricted to head/board
3. **Target group filtering**: Users only see polls they're allowed to vote on
4. **Single vote enforcement**: Database constraint + application logic
5. **XSS protection**: All output properly escaped with htmlspecialchars()
6. **SQL injection prevention**: All queries use prepared statements
7. **CSRF protection**: Forms use POST method (site-wide CSRF tokens if implemented)

## Testing Checklist

- [ ] Database tables created successfully
- [ ] Navigation link appears for all users
- [ ] "Create Poll" button only visible to head/board
- [ ] Create poll form validates required fields
- [ ] Poll creation saves correctly to database
- [ ] Polls appear in list for correct target groups
- [ ] Voting form appears for non-voters
- [ ] Vote submission saves correctly
- [ ] Results display after voting
- [ ] Cannot vote twice on same poll
- [ ] Polls expire correctly after end_date
- [ ] Dark mode styling works correctly

## Known Limitations

1. Polls are single-choice only (no multiple choice)
2. No poll editing after creation
3. No poll deletion UI (can be done via database)
4. Results are visible immediately (no "close results until poll ends")
5. No export/download of results
6. No notification system for new polls

## Future Enhancements (Optional)

1. Email notifications when new poll is created
2. Poll editing capability for creators
3. Multiple choice polls
4. Poll deletion UI
5. Results export (CSV/PDF)
6. Hide results until poll closes
7. Poll templates
8. Anonymous voting option
9. Poll comments/discussion
10. Poll categories/tags

## Support

For issues or questions:
1. Check database connectivity
2. Verify file permissions
3. Check PHP error logs
4. Ensure all Auth roles are properly configured
5. Verify database migrations ran successfully
