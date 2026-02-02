# Setup Instructions for .env Configuration and Admin User

## Overview

This repository now uses a `.env` file for configuration management. The configuration is loaded by `config/config.php` without any external dependencies.

## Files

1. **`.env`** - Contains all configuration variables (database credentials, SMTP settings, etc.)
2. **`config/config.php`** - Loads and parses the `.env` file and defines PHP constants
3. **`setup_admin.php`** - One-time script to create the initial admin user

## Setup Process

### Step 1: Verify .env File

The `.env` file should already be present in the root directory with the following configuration:

```env
# User Database Configuration
DB_USER_HOST=db5019508945.hosting-data.io
DB_USER_NAME=dbs15253086
DB_USER_USER=dbu4494103
DB_USER_PASS="<YOUR_USER_DB_PASSWORD>"

# Content Database Configuration
DB_CONTENT_HOST=db5019375140.hosting-data.io
DB_CONTENT_NAME=dbs15161271
DB_CONTENT_USER=dbu2067984
DB_CONTENT_PASS="<YOUR_CONTENT_DB_PASSWORD>"

# SMTP Configuration
SMTP_HOST=smtp.ionos.de
SMTP_USER=<YOUR_SMTP_EMAIL>
SMTP_PASS="<YOUR_SMTP_PASSWORD>"
SMTP_FROM=<YOUR_SMTP_EMAIL>

# Application Settings
BASE_URL=https://intra.business-consulting.de
```

**Note:** Use quotes around password values that contain special characters like `#`, `$`, `!`, etc.

### Step 2: Create Admin User

Run the `setup_admin.php` script **once** to create the initial admin user:

```bash
php setup_admin.php
```

Or access it via web browser:
```
https://your-domain.com/setup_admin.php
```

The script will:
- Create an admin user with email: `tom.lehmann@business-consulting.de`
- Set a default password (displayed during setup - **write it down!**)
- Set role: `admin`
- Set `tfa_enabled`: `0` (2FA disabled)
- Set `is_alumni_validated`: `1` (validated)
- **Delete itself automatically** after successful execution

**IMPORTANT:** 
- **Write down the password displayed during setup**
- **Change the admin password immediately after first login!**
- The setup script contains the initial password in plain text - it will delete itself for security

### Step 3: Verify Configuration

Test that the configuration loads correctly:

```bash
php -r "require_once 'config/config.php'; echo 'Config OK: ' . DB_USER_HOST . PHP_EOL;"
```

## Security Notes

1. **Never commit `.env` to version control** - It's already in `.gitignore`
2. **Change the admin password** after first login
3. **Delete `setup_admin.php`** manually if it doesn't auto-delete
4. **Use strong passwords** for production environments
5. **Enable 2FA** for admin accounts in production

## Configuration Loading

The `config/config.php` file now:
- Loads the `.env` file using a simple custom parser (no external dependencies)
- Parses key=value pairs
- Supports comments (lines starting with `#`)
- Defines PHP constants for all configuration values
- Provides fallback values for missing entries

## Troubleshooting

### Configuration not loading
- Verify `.env` file exists in the root directory
- Check file permissions (must be readable by PHP)
- Verify no syntax errors in `.env` file

### Admin user creation fails
- Check database connectivity
- Verify user database credentials in `.env`
- Ensure the `users` table exists (run schema from `sql/user_database_schema.sql`)
- Check if user already exists

### Setup script doesn't delete
- May be a permissions issue
- Delete manually: `rm setup_admin.php`
- File is also in `.gitignore` to prevent accidental commits
