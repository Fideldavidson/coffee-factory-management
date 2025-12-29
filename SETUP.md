# Coffee Factory CMS - Setup Guide

## Quick Start Guide

This guide will help you set up the Coffee Factory Management System on your local machine using XAMPP.

## Step 1: Install XAMPP

1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Run the installer
3. Install to default location (C:\xampp)
4. Select Apache and MySQL components during installation

## Step 2: Start Services

1. Open XAMPP Control Panel
2. Click "Start" for Apache
3. Click "Start" for MySQL
4. Both should show green "Running" status

## Step 3: Place Project Files

1. Copy the `coffee-factory-management-system` folder
2. Paste it into `C:\xampp\htdocs\`
3. Final path should be: `C:\xampp\htdocs\coffee-factory-management-system\`

## Step 4: Create Database

### Method 1: Using phpMyAdmin (Recommended)

1. Open your browser
2. Go to: `http://localhost/phpmyadmin`
3. Click "Import" in the top menu
4. Click "Choose File"
5. Navigate to your project folder
6. Select `database.sql`
7. Click "Go" at the bottom
8. Wait for "Import has been successfully finished" message

### Method 2: Using MySQL Command Line

```bash
# Open XAMPP Shell or Command Prompt
cd C:\xampp\mysql\bin
mysql -u root -p
# Press Enter when asked for password (default is no password)

# Then run:
source C:/xampp/htdocs/coffee-factory-management-system/database.sql
```

## Step 5: Verify Database

1. In phpMyAdmin, click "coffee_factory_cms" database in left sidebar
2. You should see 4 tables:
   - users
   - farmers
   - deliveries
   - inventory_batches
3. Click on "users" table
4. You should see 3 default users

## Step 6: Access the System

1. Open your web browser
2. Go to: `http://localhost/coffee-factory-management-system/`
3. You should see the login page

## Step 7: Login

Use one of these default accounts:

**Manager (Full Access):**
- Email: `admin@coffee.com`
- Password: `admin123`

**Clerk (Limited Access):**
- Email: `clerk@coffee.com`
- Password: `admin123`

## Configuration (Optional)

### Change Database Password

If you set a MySQL root password:

1. Open `config/database.php`
2. Update line:
   ```php
   define('DB_PASS', 'your_password_here');
   ```

### Change Base URL

If your project is in a different folder:

1. Open `includes/functions.php`
2. Find the `baseUrl()` function
3. Update the `$base` variable:
   ```php
   $base = '/your-folder-name';
   ```

## Common Issues & Solutions

### Issue: "Database connection failed"

**Solutions:**
- Check if MySQL is running in XAMPP Control Panel
- Verify database was imported successfully
- Check database credentials in `config/database.php`

### Issue: "Page not found" or 404 errors

**Solutions:**
- Verify Apache is running in XAMPP
- Check project is in correct folder: `C:\xampp\htdocs\coffee-factory-management-system\`
- Try accessing: `http://localhost/coffee-factory-management-system/login.php` directly

### Issue: Blank white page

**Solutions:**
- Enable PHP error display:
  - Open `C:\xampp\php\php.ini`
  - Find `display_errors = Off`
  - Change to `display_errors = On`
  - Restart Apache in XAMPP
- Check PHP error log in `C:\xampp\php\logs\php_error_log`

### Issue: Session errors

**Solutions:**
- Check if `C:\xampp\tmp` folder exists
- Verify folder has write permissions
- Restart Apache

### Issue: Cannot login

**Solutions:**
- Clear browser cache and cookies
- Try different browser
- Verify users table has data (check in phpMyAdmin)
- Check if sessions are working (create `test.php` with `<?php session_start(); echo "Sessions work!"; ?>`)

## Testing the System

After successful login, test these features:

1. **Add a Farmer**
   - Go to Farmers → Add Farmer
   - Fill in test data
   - Click Save

2. **Record a Delivery**
   - Go to Deliveries → Record Delivery
   - Select the farmer you just created
   - Enter quantity and grade
   - Click Record

3. **Process Delivery**
   - Go to Deliveries list
   - Click "View" on your delivery
   - Move it through the workflow

4. **Check Inventory**
   - Go to Inventory
   - Your approved delivery should appear as a batch

## Security Recommendations

### After Installation:

## Security Best Practices

### Post-Installation Requirements:

1. **Immediate Password Change**
   - The default `admin123` password MUST be changed via the User Management panel immediately.
2. **CSRF Consistency**
   - Ensure all custom forms include the `csrf_token` hidden input as defined in the technical documentation.
3. **Database Isolation** (Production)
   - Do not use the `root` user for the application in a production environment. Create a dedicated `coffee_factory` user with limited privileges.
4. **SSL/TLS**
   - For any network deployment, ensure the system is served over HTTPS to protect session cookies.

## System Verification

To ensure the system is hardened correctly:
- **Test Access**: Log in as a Farmer and attempt to browse back to `/users/index.php`. You should be redirected with an error.
- **Test Deletion**: Attempt to delete a user via a raw GET request (e.g., `/users/delete.php?id=123`). The system should block the request.

## Backup Recommendations

### Database Backup

1. Open phpMyAdmin
2. Click "coffee_factory_cms" database
3. Click "Export"
4. Select "Quick" export method
5. Click "Go"
6. Save the .sql file with date in filename

### File Backup

Copy entire project folder:
```
C:\xampp\htdocs\coffee-factory-management-system\
```

## Next Steps

1. Customize the system for your needs
2. Add real farmer data
3. Start recording deliveries
4. Generate reports
5. Train your staff on the system

## Getting Help

If you encounter issues:

1. Check this guide's troubleshooting section
2. Verify all steps were followed correctly
3. Check XAMPP error logs
4. Review PHP error messages (enable display_errors)

## System Requirements

- **OS**: Windows 7 or later
- **RAM**: 2GB minimum, 4GB recommended
- **Disk Space**: 500MB for XAMPP + project
- **Browser**: Chrome, Firefox, Edge, or Safari (latest version)

## Development Mode

To enable detailed error messages for development:

1. Edit `config/database.php`
2. Add at top of file:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

**Remember to disable this in production!**

---

**Setup Complete!** You should now have a fully functional Coffee Factory Management System.
