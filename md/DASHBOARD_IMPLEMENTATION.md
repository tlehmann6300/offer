# Dashboard Implementation Summary

## Overview
This document describes the implementation of an enhanced dashboard for board members, alumni board members, and managers in the IBC Intranet system.

## Requirements Implemented

### 1. Dashboard Tiles (Kacheln)

#### Im Lager (In Stock)
Shows available inventory statistics visible only to privileged roles:
- **Gesamtbestand**: Total number of units currently in stock
- **Verschiedene Artikel**: Number of unique items with stock > 0
- **Wert im Lager**: Total monetary value of inventory in stock (calculated as sum of current_stock × unit_price)

#### Unterwegs (On Route / Checked Out)
Shows items currently checked out by members with detailed information:
- Summary statistics:
  - Active checkouts count
  - Total quantity checked out
- Detailed table showing:
  - **Artikel**: Item name (with link to item details)
  - **Menge**: Quantity checked out (with unit)
  - **Entleiher**: Borrower's email address
  - **Zielort**: Destination (where the item is being used)

If no items are checked out, displays a friendly message indicating all items are in stock.

### 2. Ausschuss-Bericht (Write-off Report)

#### Verlust/Defekt diesen Monat (Loss/Defect This Month)
Warning box displayed only when write-offs exist for the current month:
- Shows as a red alert box with icon
- Summary line shows:
  - Number of write-off reports
  - Total units affected
- Detailed table includes:
  - **Datum**: Date when write-off was reported
  - **Artikel**: Item name (with link to item details)
  - **Menge**: Quantity written off (shown in red)
  - **Gemeldet von**: Email of user who reported the write-off
  - **Grund**: Reason/comment for the write-off

The warning box only appears if there are write-offs in the current month. If there are no write-offs, the section is hidden completely.

### 3. Berechtigungen (Permissions)

The enhanced dashboard sections are restricted based on role hierarchy:

**Can see enhanced dashboard (extended access):**
- `admin` (level 4)
- `board` (level 3)
- `alumni_board` (level 3)
- `manager` (level 2)

**See only standard dashboard:**
- `member` (level 1)
- `alumni` (level 1)

Permission check uses `AuthHandler::hasPermission('manager')` which leverages the existing role hierarchy system.

## Technical Implementation

### New Model Methods (includes/models/Inventory.php)

1. **getInStockStats()**
   - Returns: Array with total_in_stock, unique_items_in_stock, total_value_in_stock
   - Queries: inventory table for stock and value calculations

2. **getCheckedOutStats()**
   - Returns: Array with total_checked_out, total_quantity_out, and checkouts array
   - Queries: inventory_checkouts table joined with inventory
   - Fetches user information from user database for borrower emails
   - Includes empty array check to prevent SQL errors

3. **getWriteOffStatsThisMonth()**
   - Returns: Array with total_writeoffs, total_quantity_lost, and writeoffs array
   - Queries: inventory_history table for change_type='writeoff' in current month
   - Fetches user information from user database for reporter emails
   - Includes empty array check to prevent SQL errors

### Dashboard Page Updates (pages/dashboard/index.php)

- Added permission check: `$hasExtendedAccess = AuthHandler::hasPermission('manager')`
- Conditionally loads extended statistics only when user has permission
- Added three new sections wrapped in `<?php if ($hasExtendedAccess): ?>`
- Maintains backward compatibility with standard dashboard for regular users

## Design Features

- **Color coding**: 
  - Green for "Im Lager" (positive/available)
  - Orange for "Unterwegs" (in use/transit)
  - Red for "Ausschuss" (loss/defect/warning)
- **Icons**: Font Awesome icons for visual clarity
- **Responsive**: Uses CSS grid layout that adapts to screen size
- **Scrollable tables**: Max height with overflow-y-auto for long lists
- **Hover effects**: Subtle hover states on table rows
- **Links**: Item names link to detailed item views

## Security Considerations

1. **Role-based access control**: Only authorized roles can see sensitive data
2. **SQL injection prevention**: All queries use prepared statements with parameterized inputs
3. **XSS prevention**: All output is escaped with `htmlspecialchars()`
4. **Empty array checks**: Added checks before SQL IN clauses to prevent invalid queries
5. **Data validation**: Permission checks happen on server-side, not just UI hiding

## Testing

Two test files were created:
- `tests/test_dashboard_stats.php`: Verifies new methods exist and tests their structure
- `tests/test_dashboard_permissions.php`: Documents and verifies role-based access control

Tests confirm:
- All three new methods exist and are callable
- Permission hierarchy works correctly
- Only manager+ roles see extended dashboard
- Regular members and alumni see standard dashboard only

## Notes

- The implementation maintains backward compatibility
- No database schema changes were required
- Leverages existing checkout system and write-off tracking
- All text is in German to match the existing application language
- Implementation follows existing code patterns and style
