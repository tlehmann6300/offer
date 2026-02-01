#!/bin/bash
# IBC Intranet - Database Setup Script
# 
# SECURITY NOTE: This script should use environment variables for credentials
# Export these variables before running:
# export DB_USER_HOST="..."
# export DB_USER_USER="..."
# export DB_USER_PASS="..."
# export DB_CONTENT_HOST="..."
# export DB_CONTENT_USER="..."
# export DB_CONTENT_PASS="..."

echo "========================================="
echo "IBC Intranet - Database Setup"
echo "========================================="
echo ""

# Check if environment variables are set
if [ -z "$DB_USER_PASS" ] || [ -z "$DB_CONTENT_PASS" ]; then
    echo "ERROR: Database credentials not found in environment variables"
    echo ""
    echo "Please set the following environment variables:"
    echo "  export DB_USER_HOST='your_user_db_host'"
    echo "  export DB_USER_NAME='your_user_db_name'"
    echo "  export DB_USER_USER='your_user_db_username'"
    echo "  export DB_USER_PASS='your_user_db_password'"
    echo "  export DB_CONTENT_HOST='your_content_db_host'"
    echo "  export DB_CONTENT_NAME='your_content_db_name'"
    echo "  export DB_CONTENT_USER='your_content_db_username'"
    echo "  export DB_CONTENT_PASS='your_content_db_password'"
    echo ""
    exit 1
fi

# User Database
echo "Setting up User Database..."
mysql -h "$DB_USER_HOST" -u "$DB_USER_USER" -p"$DB_USER_PASS" "$DB_USER_NAME" < sql/user_database_schema.sql
if [ $? -eq 0 ]; then
    echo "✓ User database setup completed"
else
    echo "✗ User database setup failed"
    exit 1
fi

echo ""

# Content Database
echo "Setting up Content Database..."
mysql -h "$DB_CONTENT_HOST" -u "$DB_CONTENT_USER" -p"$DB_CONTENT_PASS" "$DB_CONTENT_NAME" < sql/content_database_schema.sql
if [ $? -eq 0 ]; then
    echo "✓ Content database setup completed"
else
    echo "✗ Content database setup failed"
    exit 1
fi

echo ""
echo "========================================="
echo "Setup completed successfully!"
echo "========================================="
echo ""
echo "IMPORTANT: Create the initial admin user manually"
echo "Run this SQL query in your User database:"
echo ""
echo "INSERT INTO users (email, password_hash, role, tfa_enabled)"
echo "VALUES ('admin@ibc.de', PASSWORD_HASH, 'admin', 0);"
echo ""
echo "Generate password hash using PHP:"
echo "php -r \"echo password_hash('YourSecurePassword', PASSWORD_ARGON2ID);\""
echo ""
