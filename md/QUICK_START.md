# IBC Intranet System - Quick Start Guide

## 🚀 Deployment Steps (IONOS)

### 1. Upload Files
Upload all files to your IONOS web hosting directory:
```bash
# Via FTP or File Manager
/htdocs/
  ├── config/
  ├── includes/
  ├── pages/
  ├── assets/
  ├── sql/
  ├── index.php
  └── ...
```

### 2. Set Environment Variables
Create a `.env` file or set environment variables (recommended for production):
```bash
# User Database
export DB_USER_HOST="db5019508945.hosting-data.io"
export DB_USER_NAME="dbs15253086"
export DB_USER_USER="dbu4494103"
export DB_USER_PASS="Q9!mZ7$A2v#Lr@8x"

# Content Database
export DB_CONTENT_HOST="db5019375140.hosting-data.io"
export DB_CONTENT_NAME="dbs15161271"
export DB_CONTENT_USER="dbu2067984"
export DB_CONTENT_PASS="Wort!Zahl?Wort#41254g"

# Application
export ENVIRONMENT="production"
export BASE_URL="https://your-domain.de"
```

### 3. Initialize Databases

#### Option A: Via Shell (recommended)
```bash
chmod +x setup.sh
./setup.sh
```

#### Option B: Via phpMyAdmin
1. Connect to User Database (dbs15253086)
2. Import `sql/user_database_schema.sql`
3. Connect to Content Database (dbs15161271)
4. Import `sql/content_database_schema.sql`

### 4. Create Admin User

#### Via Browser (easy)
1. Visit: `https://your-domain.de/create_admin.php`
2. Enter admin email and password
3. **IMPORTANT**: Delete `create_admin.php` immediately after!

#### Via Command Line
```bash
php create_admin.php
# Follow prompts
rm create_admin.php
```

#### Via SQL (if needed)
```sql
USE dbs15253086;
INSERT INTO users (email, password_hash, role, tfa_enabled) 
VALUES ('admin@ibc.de', '$argon2id$v=19$m=65536,t=4,p=1$...', 'admin', 0);
```
Generate password hash:
```bash
php -r "echo password_hash('YourPassword', PASSWORD_ARGON2ID);"
```

### 5. Set Directory Permissions
```bash
chmod 755 assets/uploads
chown www-data:www-data assets/uploads  # or your web server user
```

### 6. Test the System
1. Visit `https://your-domain.de`
2. Login with admin credentials
3. Enable 2FA in Profile settings
4. Create test inventory item
5. Invite a test user

---

## 📱 First Login

1. Go to `https://your-domain.de`
2. Enter admin email and password
3. If 2FA is enabled, enter 6-digit code from authenticator app
4. You're in!

---

## 🔐 Enable 2FA (Recommended for Admins)

1. Click on **Profil** in sidebar
2. Scroll to **2-Faktor-Authentifizierung**
3. Click **2FA aktivieren**
4. Scan QR code with Google Authenticator, Authy, or similar app
5. Enter the 6-digit code
6. 2FA is now enabled!

---

## 👥 Invite Users

1. Go to **Admin** → **Benutzerverwaltung**
2. Enter user's email and select role:
   - **Member**: Read-only access to inventory
   - **Manager**: Can manage inventory and adjust stock
   - **Board**: Full access (same as Admin)
   - **Admin**: Full system access
3. Click **Einladung senden**
4. Copy the invitation link
5. Send link to the user via email or secure channel

---

## 📦 Add Inventory Items

1. Go to **Inventar** → **Neuer Artikel**
2. Fill in:
   - Name (required)
   - Description
   - Category
   - Location
   - Current Stock
   - Minimum Stock (for low stock warnings)
   - Unit (e.g., Stück, kg, m)
   - Unit Price
   - Notes
3. Upload image (optional, max 5MB)
4. Click **Artikel erstellen**

---

## 📊 Adjust Stock

1. Go to **Inventar** and click on an item
2. In the right sidebar, use quick buttons or enter amount:
   - **+1, +10**: Add to stock
   - **-1, -10**: Remove from stock
3. Select reason from dropdown (required)
4. Add comment explaining the change (required)
5. Click **Bestätigen**
6. Change is logged in history!

---

## 🔍 View History

Every inventory change is tracked:
- Who made the change
- When it was made
- What was changed (old → new stock)
- Why (reason)
- Additional comments

View history at the bottom of any item page.

---

## 📋 Common Tasks

### Reset User Password
As admin, you can delete and re-invite users, or manually update in database:
```sql
USE dbs15253086;
UPDATE users SET password_hash = '$argon2id$...' WHERE email = 'user@example.com';
```

### Add New Category
```sql
USE dbs15161271;
INSERT INTO categories (name, description, color) 
VALUES ('New Category', 'Description', '#3B82F6');
```

### Add New Location
```sql
USE dbs15161271;
INSERT INTO locations (name, description, address) 
VALUES ('New Location', 'Description', 'Address');
```

### View Audit Logs
Go to **Admin** → **Audit-Logs** to see all system activities.

---

## 🛠 Troubleshooting

### Can't Login
- Check if account is locked (wait 15 minutes after 5 failed attempts)
- Verify database credentials in config
- Check PHP error logs

### 2FA Code Not Working
- Ensure phone/server time is synchronized
- Try the code before and after the current one
- Disable 2FA via database if needed:
  ```sql
  UPDATE users SET tfa_enabled = 0 WHERE email = 'your@email.com';
  ```

### Image Upload Fails
- Check `assets/uploads` directory exists and is writable
- Verify file size is under 5MB
- Check allowed MIME types in config

### Database Connection Error
- Verify database credentials
- Check if database server is accessible
- Review error logs

---

## 📞 Support

For technical issues:
1. Check **Admin** → **Audit-Logs** for error details
2. Review PHP error logs on server
3. Contact system administrator

---

## 🎯 Best Practices

### Security
- ✅ Enable 2FA for all admin/board users
- ✅ Use strong passwords (min 8 characters)
- ✅ Regularly review audit logs
- ✅ Keep inventory comments descriptive
- ✅ Use HTTPS only (no HTTP)

### Inventory Management
- ✅ Always add comments when adjusting stock
- ✅ Use meaningful reasons for adjustments
- ✅ Set minimum stock levels for important items
- ✅ Regularly review low stock warnings
- ✅ Add photos to inventory items

### User Management
- ✅ Assign appropriate roles (principle of least privilege)
- ✅ Remove inactive users
- ✅ Use invitation system (don't create users directly in DB)
- ✅ Monitor user activity via audit logs

---

## 📈 Next Steps

After basic setup:
1. ✅ Import existing inventory data
2. ✅ Invite all team members
3. ✅ Configure categories and locations
4. ✅ Set up minimum stock levels
5. ✅ Train users on the system
6. ✅ Establish backup routine

---

## 🎨 Customization

### Change Colors
Edit `includes/templates/main_layout.php`:
```css
:root {
    --primary: #667eea;    /* Main purple */
    --secondary: #764ba2;   /* Secondary purple */
}
```

### Change Logo/Title
Edit `includes/templates/main_layout.php` line 51:
```html
<h1 class="text-2xl font-bold mb-8">
    <i class="fas fa-building mr-2"></i>
    Your Organization Name
</h1>
```

---

## ✅ System Ready!

Your IBC Intranet System is now fully configured and ready to use. Enjoy managing your inventory with style! 🎉

For detailed technical documentation, see:
- `README.md` - Complete system documentation
- `DEPLOYMENT.md` - Detailed deployment guide
- `IMPLEMENTATION_SUMMARY.md` - Technical implementation details
- `VERIFICATION_CHECKLIST.md` - Requirements verification
