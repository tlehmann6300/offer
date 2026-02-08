# SQL Migration: Add Invoice Paid Fields

This migration adds support for tracking when invoices are marked as paid and by whom.

## Description

Adds two new columns to the `invoices` table:
- `paid_at` (DATETIME): Timestamp when invoice was marked as paid
- `paid_by_user_id` (INT UNSIGNED): User ID of the board member who marked it as paid

## How to Apply

Run the following SQL file on the invoice database (dbs15251284):

```bash
mysql -u [username] -p dbs15251284 < add_invoice_paid_fields.sql
```

Or manually execute the SQL statements from `add_invoice_paid_fields.sql`.

## Dependencies

- Invoice model updated with `markAsPaid()` method
- API endpoint `api/mark_invoice_paid.php` created
- Invoice index page updated to show "Als Bezahlt markieren" button

## Notes

- Foreign key constraint to users table cannot be added because tables are in different databases
- Application-level validation ensures data integrity
- Only board members with 'Finanzen und Recht' position can mark invoices as paid
