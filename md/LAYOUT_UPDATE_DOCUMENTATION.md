# Layout Overhaul and User Experience Update Documentation

## Overview
This document describes the layout overhaul and user experience improvements implemented for the IBC Intranet system.

## Changes Implemented

### 1. Header & Sidebar Greeting
**Location:** `includes/templates/main_layout.php`

**Changes:**
- Updated user greeting from displaying email to "Guten Tag, [First Name] [Last Name]"
- Name is fetched from `alumni_profiles` table first, then falls back to `users` table
- If no name is available, displays the user's email address
- Role display now includes German translations:
  - `candidate` → "Anwärter"
  - `member` → "Mitglied"
  - `head` → "Ressortleiter"
  - `board` → "Vorstand"
  - `alumni` → "Alumni"
  - `alumni_board` → "Alumni Vorstand"

### 2. Dynamic Date/Time Display
**Location:** `includes/templates/main_layout.php`

**Changes:**
- Added dynamic date/time display at the bottom of the sidebar
- Format: DD.MM.YYYY HH:MM (e.g., "08.02.2026 14:00")
- Updates automatically every minute using JavaScript
- Positioned below the logout button with border separator

### 3. Sidebar Structure (Role-Based)
**Location:** `includes/templates/main_layout.php`

**Changes:**
- Reorganized sidebar menu items according to role-based access:
  - **Everyone:** Dashboard, Profil, Inventar, Events, Projekte, Blog
  - **Board & Alumni Board:** Rechnungen (Invoices)
  - **Board Only:** Verwaltung (Administration) dropdown with:
    - Benutzer (Users)
    - Einstellungen (Settings)
    - System-Check (Database Maintenance)
- Added "Profil" link to sidebar for easy access to profile settings
- Removed "Mitglieder" from general navigation (was only for board/head/member)
- Implemented collapsible dropdown menu for "Verwaltung" section

### 4. Dark/Light Mode Implementation
**Locations:** 
- `includes/templates/main_layout.php`
- `assets/css/theme.css`

**Changes:**
- Added theme toggle button in sidebar with Sun/Moon icon
- Button shows current mode and switches icon based on active theme
- JavaScript implementation supports:
  - Manual toggle between light and dark modes
  - Preference saved to localStorage
  - Database preference loaded on page load (via `data-user-theme` attribute)
  - Auto mode that respects system preferences
- CSS Dark Mode Variables added:
  - Background colors: `--bg-primary`, `--bg-secondary`, `--bg-tertiary`
  - Text colors: `--text-primary`, `--text-secondary`
  - Border colors: `--border-color`
  - Applied to cards, tables, inputs, and other UI elements

**Dark Mode Colors:**
- Background Primary: `#1a1a1a`
- Background Secondary: `#2d2d2d`
- Background Tertiary: `#333333`
- Text Primary: `#f0f0f0`
- Text Secondary: `#b0b0b0`
- Border Color: `#444444`

### 5. User Profile Settings
**Location:** `pages/auth/profile.php`

**Existing Sections:**
- Account Information
- Change Email
- Profile Information (with role-specific fields)
- Change Password
- 2FA Settings
- Notification Preferences

**New Section Added:**
- **Theme Settings:** Allows users to choose between:
  - **Auto:** Uses system preferences
  - **Light:** Always uses light mode
  - **Dark:** Always uses dark mode
- Theme preference saved to `users.theme_preference` column in database

### 6. Database Changes
**Location:** `sql/add_theme_preference_column.sql`

**New Column:**
```sql
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS theme_preference VARCHAR(10) DEFAULT 'auto' 
COMMENT 'User theme preference: auto, light, or dark';
```

**Migration Required:**
To add the theme preference column, run:
```bash
mysql -u [username] -p [database_name] < sql/add_theme_preference_column.sql
```

### 7. User Model Updates
**Location:** `includes/models/User.php`

**New Method:**
```php
public static function updateThemePreference($userId, $theme)
```
- Validates theme value (must be 'auto', 'light', or 'dark')
- Updates user's theme preference in database
- Returns boolean indicating success/failure

## Technical Implementation Details

### Theme Toggle Logic
1. On page load:
   - Check localStorage for theme preference
   - If not found, use database preference from `data-user-theme` attribute
   - If set to 'auto', detect system preference using `window.matchMedia`
   
2. When toggle button clicked:
   - Toggles between light and dark modes
   - Saves preference to localStorage
   - Updates UI to reflect current theme

3. Theme persistence:
   - localStorage provides instant theme application
   - Database preference serves as default for new sessions
   - Users can update database preference in profile settings

### Dropdown Menu Implementation
- Uses JavaScript click handler for toggle functionality
- Works on both mobile and desktop
- Closes when clicking outside the dropdown
- Chevron icon rotates to indicate open/closed state

### Mobile Responsiveness
- Sidebar is hidden by default on mobile (< 1024px)
- Mobile menu button shows/hides sidebar
- Dropdown menus work properly on mobile with click handlers
- Date/time display is responsive and doesn't overflow

## Testing Checklist

- [ ] Verify greeting displays correct name from database
- [ ] Verify email fallback when name is not available
- [ ] Verify date/time updates every minute
- [ ] Test dark/light mode toggle
- [ ] Verify theme preference saves to localStorage
- [ ] Verify theme preference saves to database
- [ ] Test "Verwaltung" dropdown for board users
- [ ] Verify "Rechnungen" only visible to board and alumni_board
- [ ] Test mobile sidebar functionality
- [ ] Verify profile settings page loads correctly
- [ ] Test theme selection in profile settings
- [ ] Verify notification preferences work correctly

## Browser Compatibility
- Modern browsers supporting:
  - CSS Variables
  - localStorage API
  - ES6 JavaScript
  - CSS Grid and Flexbox
  - Media Queries (including prefers-color-scheme)

## Future Enhancements
Potential improvements for future iterations:
1. Add smooth transition animations for theme switching
2. Add more granular theme customization options
3. Implement theme preview before saving
4. Add keyboard shortcuts for theme toggle
5. Support for custom theme colors
6. Add accessibility improvements (ARIA labels, keyboard navigation)

## Rollback Instructions
If issues occur, to rollback:
1. Revert changes to `includes/templates/main_layout.php`
2. Revert changes to `assets/css/theme.css`
3. Revert changes to `pages/auth/profile.php`
4. Revert changes to `includes/models/User.php`
5. Optionally remove `theme_preference` column from database

## Support
For issues or questions, contact the development team or refer to the main project documentation.
