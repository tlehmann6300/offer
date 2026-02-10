# Event Documentation Feature Implementation

## Overview
This feature adds a "Dokumentation" (Documentation) section to event pages, visible only to board and alumni_board members.

## Features
- **Calculations field**: Text area for noting calculations, costs, and budget details
- **Sales tracking**: Add/edit/remove sales entries with label, amount, and date
- **Visual chart**: Bar chart displaying sales data using Chart.js
- **Data persistence**: All data is saved to the database

## Database Migration Required

**IMPORTANT**: Before using this feature, run the migration to create the `event_documentation` table.

### Running the Migration

```bash
php run_event_documentation_migration.php
```

Or manually execute the SQL from `sql/migration_event_documentation.sql`:

```sql
CREATE TABLE IF NOT EXISTS event_documentation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    calculations TEXT,
    sales_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_doc (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Files Added/Modified

### New Files
- `sql/migration_event_documentation.sql` - Database migration
- `includes/models/EventDocumentation.php` - Model for event documentation
- `api/save_event_documentation.php` - API endpoint to save documentation
- `run_event_documentation_migration.php` - Migration runner script

### Modified Files
- `pages/events/view.php` - Added documentation section with Chart.js integration
- `pages/dashboard/index.php` - Updated greeting to use firstname + lastname
- `pages/auth/settings.php` - Fixed theme saving with localStorage sync
- `assets/css/theme.css` - Fixed light mode text colors
- `pages/admin/stats.php` - Changed database limit display from 1 GB to 2 GB
- `pages/events/index.php` - Updated "Neues Event" button permissions
- `pages/projects/index.php` - Updated "Neues Projekt" button permissions

## Usage

### For Board/Alumni Board Members
1. Navigate to any event detail page (`pages/events/view.php?id=X`)
2. Scroll down to the "Dokumentation" section (only visible to board/alumni_board)
3. Enter calculations in the text area
4. Add sales entries using the "Verkauf hinzufügen" button
5. Fill in label, amount, and date for each sale
6. The chart updates automatically as you add/edit sales
7. Click "Dokumentation speichern" to save all changes

### Permissions
- Only users with role `board` or `alumni_board` can:
  - View the documentation section
  - Edit calculations
  - Add/edit/delete sales data
  - Save documentation

## Technical Details

### Chart.js
- Loaded from CDN: `https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js`
- Only loaded when documentation section is visible (board/alumni_board users)
- Bar chart shows sales amounts with labels

### Data Structure
Sales data is stored as JSON array:
```json
[
  {
    "label": "Ticketverkauf",
    "amount": 150.50,
    "date": "2024-01-15"
  },
  {
    "label": "Getränkeverkauf",
    "amount": 75.00,
    "date": "2024-01-15"
  }
]
```

## Security
- Authentication required for all documentation operations
- Authorization check for board/alumni_board roles
- Input validation on API endpoint
- Prepared SQL statements to prevent injection
- CSRF protection via session validation
