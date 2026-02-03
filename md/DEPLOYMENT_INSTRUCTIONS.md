# Deployment Instructions

## Important: Database Migration Required

After deploying this update to your live server, you **must** run the database migration to add the required new columns to the events table.

### Step 0: Verify Current Database Schema (Optional)

Before making changes, you can verify what's missing:
1. Navigate to: `https://your-domain.de/verify_db_schema.php`
2. This will show you:
   - Which columns exist and which are missing
   - Whether the uploads directory is properly configured
   - A complete list of all event table columns
3. This script is safe to run and only reads data (no modifications)

### Step 1: Execute Database Migration

The PHP code now uses new database columns (`maps_link`, `registration_start`, `registration_end`) that need to be added to your database.

**Choose ONE of these methods:**

#### Option A: Using the SQL Migration Script (Recommended)
1. Navigate to: `https://your-domain.de/sql/migrate_add_event_fields.php`
2. The script will:
   - Check which columns already exist
   - Add missing columns: `maps_link`, `registration_start`, `registration_end`
   - Update the `location` column length if needed
3. You should see "✓" success messages for each operation
4. Once complete, the migration is done

#### Option B: Using the Quick Fix Script
1. Navigate to: `https://your-domain.de/fix_event_db.php`
2. This script will:
   - Add the `maps_link` column if missing
   - Add the `image_path` column if missing
3. The script will **self-delete** after successful execution
4. You should see "✓ Datenbank repariert" (Database repaired)

**Note:** Both scripts are safe to run multiple times - they check if columns exist before adding them.

### Step 2: Verify Upload Directory Permissions

The `/uploads/events/` directory has been created for storing event images.

**Action Required:**
1. Verify the directory exists on your server: `/uploads/events/`
2. Set proper permissions so the web server can write files:
   ```bash
   chmod 755 uploads/events
   # OR if 755 doesn't work:
   chmod 777 uploads/events
   ```

### Step 3: Verify IBC Theme Colors

The theme.css file now uses IBC corporate identity colors:
- **IBC Green**: `#00a651` (primary action color)
- **IBC Blue**: `#0066b3` (secondary brand color)
- **IBC Accent**: `#ff6b35` (highlight color)

These colors are already configured in `assets/css/theme.css`. No action needed unless you want to customize further.

### Troubleshooting

#### Error: "Unknown column 'maps_link'"
- **Solution:** You haven't run the database migration yet. Follow Step 1 above.

#### Error: "Failed to write file" when uploading images
- **Solution:** Check that `/uploads/events/` directory exists and has write permissions (Step 2).

#### Theme still shows standard colors
- **Solution:** Clear your browser cache or do a hard refresh (Ctrl+F5 / Cmd+Shift+R).

### Security Note

After running the migration scripts successfully, you should:
1. **Delete these files from production** for security:
   - `verify_db_schema.php`
   - `sql/migrate_add_event_fields.php` 
   - `fix_event_db.php`
2. Or protect them with .htaccess rules to prevent unauthorized access

These scripts are safe to delete after successful migration as they're only needed once.
