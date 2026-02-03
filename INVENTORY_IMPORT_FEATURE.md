# Inventory Mass Import Feature

This document describes the inventory mass import feature that allows administrators and managers to import multiple inventory items from a JSON file.

## Overview

The mass import feature enables bulk uploading of inventory items through a JSON file upload interface, streamlining the process of adding multiple items to the inventory system.

## Features

- **JSON File Upload**: Upload inventory items in JSON format
- **Field Validation**: Validates required and optional fields
- **Duplicate Prevention**: Checks for duplicate serial numbers before import
- **Auto-creation**: Automatically creates categories and locations if they don't exist
- **Detailed Feedback**: Shows number of items imported, skipped, and detailed error messages
- **Role-based Access**: Only visible to users with manager or admin permissions

## Database Migration

Before using the import feature, apply the database migration to add required fields:

```bash
php apply_migration.php
```

This adds the following fields to the `inventory` table:
- `serial_number` (VARCHAR 100) - Optional unique identifier
- `status` (ENUM) - Item status: 'available', 'in_use', 'maintenance', 'retired'
- `purchase_date` (DATE) - Optional purchase date

## JSON Format

The import file must be a valid JSON array of objects. Each object represents one inventory item.

### Required Fields

- **name** (string): Name of the item
- **category** (string): Category name (will be created if doesn't exist)

### Optional Fields

- **status** (string): One of: 'available', 'in_use', 'maintenance', 'retired' (default: 'available' if not provided)
- **description** (string): Item description
- **serial_number** (string): Unique serial number (duplicates will be rejected)
- **location** (string): Location name (will be created if doesn't exist)
- **purchase_date** (string): Purchase date in format YYYY-MM-DD

### Example JSON

```json
[
  {
    "name": "Laptop Dell XPS 15",
    "category": "IT-Equipment",
    "status": "available",
    "description": "15 Zoll Laptop mit i7 Prozessor und 16GB RAM",
    "serial_number": "DXPS123456",
    "location": "Büro München",
    "purchase_date": "2024-01-15"
  },
  {
    "name": "Whiteboard mobil",
    "category": "Büromöbel",
    "status": "available",
    "description": "Mobiles Whiteboard 180x120cm",
    "location": "Konferenzraum 2"
  }
]
```

A complete sample file is available at: `samples/inventory_import_sample.json`

## How to Use

1. **Access the Import Feature**
   - Navigate to the Inventory page (pages/inventory/index.php)
   - Click the "Massenimport" (Mass Import) button (only visible to managers/admins)

2. **Prepare Your JSON File**
   - Create a JSON file following the format described above
   - Ensure all required fields are present
   - Verify serial numbers are unique

3. **Upload and Import**
   - Click "Choose File" and select your JSON file
   - Review the format requirements in the modal
   - Click "Importieren" (Import) to start the import

4. **Review Results**
   - The system will display the number of items imported
   - Any skipped items or errors will be shown with details
   - Check the error details to fix issues and re-import if needed

## Import Logic

1. **Validation**: Each item is validated for required fields and valid status values
2. **Duplicate Check**: If a serial_number is provided, checks if it already exists
3. **Category/Location Creation**: Creates categories and locations if they don't exist
4. **Date Parsing**: Parses and validates purchase_date format
5. **Item Creation**: Inserts item into database with default stock of 1
6. **History Logging**: Logs the import action in inventory_history

## Error Handling

The import process continues even if individual items fail. Each error is logged with:
- Item index in the JSON array
- Item name (if available)
- Specific error message

Common errors:
- Missing required fields (name, category)
- Invalid status value
- Duplicate serial number
- Invalid date format
- Database errors

## Testing

Run the test suite to validate the import logic:

```bash
php tests/test_inventory_import.php
```

This validates:
- JSON structure
- Required fields validation
- Status value validation
- Date format parsing
- Serial number uniqueness logic

## Security

- Access restricted to manager and admin roles
- File upload validates JSON format
- SQL injection prevention through prepared statements
- XSS prevention through output escaping
- All imports are logged in inventory_history with user ID

## API Reference

### Inventory::importFromJson($data, $userId)

Imports inventory items from an array of item data.

**Parameters:**
- `$data` (array): Array of item objects from JSON
- `$userId` (int): ID of user performing the import

**Returns:**
```php
[
    'success' => bool,      // True if at least one item was imported
    'imported' => int,      // Number of successfully imported items
    'skipped' => int,       // Number of skipped items
    'errors' => array       // Array of error messages
]
```

**Example:**
```php
$jsonContent = file_get_contents($uploadedFile);
$data = json_decode($jsonContent, true);
$result = Inventory::importFromJson($data, $_SESSION['user_id']);

if ($result['success']) {
    echo "Imported {$result['imported']} items";
}
```

## Files Modified/Added

### New Files
- `sql/migrations/add_inventory_import_fields.sql` - Database migration
- `samples/inventory_import_sample.json` - Sample import file
- `tests/test_inventory_import.php` - Test suite
- `apply_migration.php` - Migration application script
- `INVENTORY_IMPORT_FEATURE.md` - This documentation

### Modified Files
- `sql/content_database_schema.sql` - Updated schema
- `includes/models/Inventory.php` - Added importFromJson method
- `pages/inventory/index.php` - Added import UI and handling

## Troubleshooting

**Issue**: Migration fails with "Duplicate column name"
- **Solution**: Column already exists, migration already applied

**Issue**: Items not importing
- **Solution**: Check JSON format, ensure required fields present

**Issue**: Serial number duplicates
- **Solution**: Check existing inventory for duplicate serial numbers

**Issue**: Import button not visible
- **Solution**: Ensure user has manager or admin role

## Future Enhancements

Potential improvements:
- Export inventory to JSON
- CSV format support
- Bulk update via import
- Import preview before committing
- Progress bar for large imports
- Rollback functionality
