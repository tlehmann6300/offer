# Production Content Database Setup Guide

## Overview
This guide explains how to configure the production Content Database (used for Invoices) using the provided setup script.

## Prerequisites
- Access to the production server
- Database credentials for the Content DB
- Write permissions on the `.env` file

## Setup Steps

### Step 1: Run the Configuration Script

1. Navigate to the root directory of the project in your web browser:
   ```
   https://your-domain.com/setup_production_db.php
   ```

2. The script will display the hardcoded production credentials:
   - **DB_CONTENT_HOST**: Production host URL
   - **DB_CONTENT_PORT**: `3306`
   - **DB_CONTENT_USER**: Production database user
   - **DB_CONTENT_PASS**: (displayed as dots for security)

3. Enter the **DB_CONTENT_NAME** in the form (e.g., `dbs12345678`)
   - The name must start with "dbs" followed by numbers
   - This is validated on both client and server side

4. Click "Configure Production Database"

5. The script will:
   - Read the existing `.env` file
   - Update or append the Content DB credentials
   - Save the changes back to `.env`

6. Upon success, you'll see:
   ```
   ✅ Content Database configured. Now run migrations.
   ```

### Step 2: Delete the Setup Script

**CRITICAL SECURITY STEP**: Immediately delete `setup_production_db.php` after successful configuration to prevent unauthorized access:

```bash
rm setup_production_db.php
```

### Step 3: Apply Database Schema

After configuring the `.env` file, apply the invoice system schema to the production Content database:

1. Connect to your production Content database using the credentials from `.env`

2. Run the schema file:
   ```bash
   mysql -h <DB_HOST> -u <DB_USER> -p <DB_NAME> < sql/production_content_db_invoices.sql
   ```
   Replace `<DB_HOST>`, `<DB_USER>`, and `<DB_NAME>` with your actual database credentials from .env file.

   Or use a database management tool (phpMyAdmin, MySQL Workbench, etc.) to import the file:
   ```
   sql/production_content_db_invoices.sql
   ```

### Step 4: Run Migrations

Execute any necessary migrations:

```bash
php sql/migrate_invoice_module.php
```

This will:
- Add 'alumni_board' role to users table
- Create the invoices table (if not already created by the schema)
- Set up necessary indexes

### Step 5: Verify Setup

Run the verification script to ensure everything is configured correctly:

```bash
php verify_invoice_setup.php
```

This will check:
- Database connectivity
- Table existence
- Required roles
- Directory permissions for uploads/invoices

## Production Credentials Reference

The following credentials are configured by the setup script and stored in `.env`:

| Variable | Value |
|----------|-------|
| DB_CONTENT_HOST | (configured in .env via setup script) |
| DB_CONTENT_PORT | 3306 |
| DB_CONTENT_USER | (configured in .env via setup script) |
| DB_CONTENT_PASS | (stored securely in .env) |
| DB_CONTENT_NAME | (user-provided, e.g., dbs12345678) |

**Note**: These credentials are for the Content Database only, which is separate from the User Database.
**Security**: All credentials are hardcoded in `setup_production_db.php` and transferred to `.env` during setup.

## Database Schema

The production Content database includes the following invoice-related tables:

### `invoices`
Main table for invoice/receipt tracking
- `id`: Primary key
- `user_id`: Reference to user who submitted the invoice
- `description`: Purpose of the expense
- `amount`: Invoice amount (DECIMAL)
- `date_of_receipt`: Date of the receipt
- `file_path`: Path to uploaded receipt file
- `status`: pending | approved | rejected | paid
- `rejection_reason`: Reason if rejected
- `created_at`, `updated_at`: Timestamps

### `invoice_history` (Optional)
Audit log for invoice changes
- Tracks all status changes and modifications
- Records who made changes and when

### `invoice_categories` (Optional)
Categorization of expenses
- Pre-populated with common categories
- Supports budget limits per category

## Security Notes

1. **Delete setup_production_db.php immediately after use** - It contains hardcoded credentials
2. The script includes CSRF protection via session tokens
3. Database name validation prevents SQL injection
4. Credentials in `.env` should never be committed to version control
5. Ensure `.env` has restricted file permissions (0600 or 0644)

## Troubleshooting

### Error: ".env file not found"
- Ensure you're running the script from the project root
- Verify the `.env` file exists

### Error: "Failed to write to .env file"
- Check file permissions on `.env`
- Ensure the web server has write access

### Error: "Database name must start with 'dbs'"
- The database name must follow the pattern: `dbs` followed by numbers only
- Example: `dbs12345678`

### Connection Issues After Setup
- Verify credentials in `.env` are correct
- Test database connection manually
- Check firewall rules for database host

## Related Files

- `setup_production_db.php` - Configuration script (delete after use!)
- `sql/production_content_db_invoices.sql` - Database schema
- `sql/migrate_invoice_module.php` - Migration script
- `verify_invoice_setup.php` - Verification script
- `includes/database.php` - Database connection handler

## Support

If you encounter issues:
1. Check the error message displayed by the script
2. Review server logs for detailed error information
3. Verify all prerequisites are met
4. Ensure database credentials are correct
