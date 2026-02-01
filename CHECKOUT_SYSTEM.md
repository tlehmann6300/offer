# Inventory Checkout/Check-in System

## Overview

The IBC Intranet now includes a comprehensive checkout/check-in system for inventory management. This allows members to borrow items from the inventory, track their usage, and return them with proper documentation of any defects or losses.

## Features

### 1. Item Checkout (Ausleihen)
- **All authenticated users** can checkout items from the inventory
- Users specify:
  - Quantity to borrow
  - Purpose of use (required)
  - Destination/Location (optional)
- Stock is automatically reduced upon checkout
- Checkout is recorded in the system for tracking

### 2. Item Check-in (Rückgabe)
- Users can return borrowed items
- System asks: "Ist alles in Ordnung?" (Is everything okay?)
- Two scenarios:
  - **Everything OK**: Full quantity is returned to stock
  - **Problems**: User specifies:
    - Number of defective/lost items
    - Reason/Description of the issue
    - Only good items are returned to stock
    - Defective items are logged as "Ausschuss" (write-off)

### 3. User Checkout Management
- Users can view their active checkouts in "Meine Ausleihen" (My Checkouts)
- History of returned items is maintained
- Each checkout shows:
  - Item name
  - Quantity
  - Purpose
  - Destination
  - Checkout date
  - Return status

### 4. Admin Features

#### Category Management (`/pages/admin/categories.php`)
- Create new inventory categories
- Specify category name, description, and color
- View all existing categories
- **Required Permission**: Manager or higher (board, alumni_board, admin)

#### Location Management (`/pages/admin/locations.php`)
- Create new storage locations
- Specify location name, description, and address
- View all existing locations
- **Required Permission**: Manager or higher (board, alumni_board, admin)

## User Interface

### For All Users

#### 1. Inventory Item View Page
- **New Button**: "Entnehmen / Ausleihen" (Borrow)
  - Only visible when stock is available
  - Opens checkout form
- **New Section**: "Aktive Ausleihen" (Active Checkouts)
  - Shows who has borrowed items
  - Displays quantity, purpose, and checkout date

#### 2. Checkout Page (`checkout.php`)
- Form fields:
  - Quantity (validated against available stock)
  - Purpose (required text field)
  - Destination (optional text field)
- Validation ensures quantity doesn't exceed available stock
- Success redirects to item view page with confirmation

#### 3. My Checkouts Page (`my_checkouts.php`)
- Lists all active checkouts for the current user
- Quick access to return items
- History section shows past returns
- Indicators for defective items in history

#### 4. Check-in Page (`checkin.php`)
- Shows details of the borrowed item
- Radio buttons: "Everything OK?" / "There are problems"
- If problems:
  - Fields for defective quantity
  - Required reason/description field
- Success redirects to "My Checkouts" with confirmation

### For Managers and Above

#### 5. Categories Management
- Create new categories with custom colors
- View all existing categories
- Categories are used to organize inventory items

#### 6. Locations Management
- Create new storage locations
- Specify descriptions and addresses
- Locations track where items are stored

## Database Schema

### New Table: `inventory_checkouts`
```sql
CREATE TABLE inventory_checkouts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNSIGNED NOT NULL,           -- Reference to inventory item
    user_id INT UNSIGNED NOT NULL,           -- User who checked out
    quantity INT NOT NULL,                   -- Quantity borrowed
    purpose VARCHAR(255) NOT NULL,           -- Why it was borrowed
    destination VARCHAR(255),                -- Where it's being used
    checkout_date TIMESTAMP DEFAULT NOW(),   -- When checked out
    expected_return_date DATE,               -- Expected return (optional)
    return_date TIMESTAMP,                   -- Actual return date
    returned_quantity INT,                   -- How many returned
    defective_quantity INT DEFAULT 0,        -- How many defective
    defective_reason TEXT,                   -- Why defective
    status ENUM('checked_out', 'returned', 'partially_returned', 'overdue'),
    notes TEXT,
    FOREIGN KEY (item_id) REFERENCES inventory(id)
);
```

### Updated: `inventory_history`
Extended `change_type` ENUM to include:
- `checkout` - Item was borrowed
- `checkin` - Item was returned
- `writeoff` - Item was marked as defective/lost (Ausschuss)

### New Locations
Added via migration:
- "Furtwangen H-Bau -1.87"
- "Furtwangen H-Bau -1.88"

## Backend Methods

### Inventory Model (`includes/models/Inventory.php`)

#### `checkoutItem($itemId, $userId, $quantity, $purpose, $destination, $expectedReturnDate)`
Checks out an item from inventory.
- Validates stock availability
- Reduces current_stock
- Creates checkout record
- Logs in history as 'checkout'
- Returns: `['success' => bool, 'message' => string]`

#### `checkinItem($checkoutId, $returnedQuantity, $isDefective, $defectiveQuantity, $defectiveReason)`
Returns an item to inventory.
- Validates checkout exists and is active
- Updates stock (only good items)
- Marks checkout as returned
- Logs in history as 'checkin'
- If defective items: logs as 'writeoff'
- Returns: `['success' => bool, 'message' => string]`

#### `getItemCheckouts($itemId)`
Gets all active checkouts for a specific item.
- Returns array of checkout records
- Used to show "who has this item"

#### `getUserCheckouts($userId, $includeReturned)`
Gets checkouts for a specific user.
- `$includeReturned = false`: only active checkouts
- `$includeReturned = true`: includes history
- Returns array of checkout records with item details

#### `getCheckoutById($checkoutId)`
Gets a specific checkout record by ID.
- Includes item details
- Used for check-in page

## Workflow Examples

### Example 1: Borrow an Item
1. User navigates to inventory
2. Clicks on an item to view details
3. Clicks "Entnehmen / Ausleihen" button
4. Fills out checkout form:
   - Quantity: 3
   - Purpose: "Workshop nächste Woche"
   - Destination: "Konferenzraum A"
5. Submits form
6. System:
   - Validates stock (has 3+ available)
   - Reduces stock by 3
   - Creates checkout record
   - Shows success message

### Example 2: Return Item - Everything OK
1. User goes to "Meine Ausleihen"
2. Clicks "Zurückgeben" for an item
3. Confirms returned quantity
4. Selects "Ja, alles in Ordnung"
5. Submits
6. System:
   - Increases stock by returned quantity
   - Marks checkout as returned
   - Shows success message

### Example 3: Return Item - With Defects
1. User goes to "Meine Ausleihen"
2. Clicks "Zurückgeben" for an item
3. Confirms returned 3 items
4. Selects "Nein, es gibt Probleme"
5. Enters:
   - Defective quantity: 1
   - Reason: "Kabel beschädigt während Transport"
6. Submits
7. System:
   - Increases stock by 2 (3 - 1 defective)
   - Marks checkout as returned
   - Logs 1 item as "Ausschuss" with reason
   - Shows success message

## Permissions

| Feature | Member | Manager | Board/Alumni Board | Admin |
|---------|--------|---------|-------------------|-------|
| View Inventory | ✓ | ✓ | ✓ | ✓ |
| Checkout Items | ✓ | ✓ | ✓ | ✓ |
| Check-in Items | ✓ | ✓ | ✓ | ✓ |
| View Own Checkouts | ✓ | ✓ | ✓ | ✓ |
| Adjust Stock | ✗ | ✓ | ✓ | ✓ |
| Manage Categories | ✗ | ✓ | ✓ | ✓ |
| Manage Locations | ✗ | ✓ | ✓ | ✓ |
| Edit Items | ✗ | ✓ | ✓ | ✓ |

## Installation

### For New Installations
The checkout system is included in the base schema. Simply run:
```bash
mysql -h <host> -u <user> -p dbs15161271 < sql/content_database_schema.sql
```

### For Existing Installations
Run the migration script:
```bash
mysql -h <host> -u <user> -p < sql/migrations/002_add_checkout_system.sql
```

## Security Considerations

1. **Stock Validation**: System prevents checkouts exceeding available stock
2. **User Association**: All checkouts are tied to user accounts for accountability
3. **Audit Trail**: Complete history of all checkout/check-in operations
4. **Permission Checks**: Admin features require appropriate roles
5. **SQL Injection Protection**: All queries use prepared statements
6. **Transaction Safety**: Checkout/check-in use database transactions

## Future Enhancements

Potential improvements for future versions:
- Email notifications for overdue items
- Due date management and reminders
- Bulk check-in/checkout operations
- QR code scanning for items
- Mobile-optimized checkout flow
- Advanced search and filtering for checkouts
- Export checkout reports to CSV/PDF
- Integration with calendar for event-based checkouts

## Support

For issues or questions:
1. Check the history log in item view page
2. Review system logs (Admin → Audit-Logs)
3. Verify database migration was successful
4. Contact system administrator

## Related Documentation

- [README.md](../README.md) - Main system documentation
- [ALUMNI_SYSTEM.md](../ALUMNI_SYSTEM.md) - Alumni-specific features
- [sql/migrations/README.md](../sql/migrations/README.md) - Migration guide
