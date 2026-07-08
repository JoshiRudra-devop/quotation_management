# XAMPP Setup Guide

## Access Your Project

Your project is located at:

```
/Applications/XAMPP/xamppfiles/htdocs/Webdev/quataion/
```

### Correct URLs to Access:

1. **Home Page (HTML):**

   ```
   http://localhost/Webdev/quataion/
   ```

   or

   ```
   http://localhost/Webdev/quataion/index.html
   ```

2. **Login Page:**

   ```
   http://localhost/Webdev/quataion/login.php
   ```

3. **Test PHP:**
   ```
   http://localhost/Webdev/quataion/test.php
   ```

## Common Issues & Solutions

### 1. Page Not Showing / Blank Page

**Check:**

- Make sure Apache is running in XAMPP Control Panel
- Check the URL - make sure you're using the correct path
- Open browser developer tools (F12) and check for errors in Console

**Test:**

- Visit: `http://localhost/Webdev/quataion/test.php`
- If this works, PHP is fine. If not, check XAMPP Apache status.

### 2. Database Connection Errors

Your project uses a remote database. If you see database errors:

- Check your internet connection
- Verify database credentials in `config.php`
- The database host is: `sql207.infinityfree.com`

### 3. PHP Errors Not Showing

To see PHP errors, check:

- XAMPP error log: `/Applications/XAMPP/xamppfiles/logs/error_log`
- Browser console (F12)
- Add this to the top of your PHP files temporarily:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```

### 4. File Permissions

Make sure files are readable:

```bash
chmod 644 *.php *.html
chmod 755 .
```

## Quick Start

1. **Start XAMPP:**

   - Open XAMPP Control Panel
   - Start Apache (and MySQL if needed)

2. **Access your site:**

   - Open browser
   - Go to: `http://localhost/Webdev/quataion/`

3. **If still not working:**
   - Check XAMPP Control Panel for errors
   - Review error logs
   - Test with `test.php` file

## Troubleshooting Steps

1. ✅ Apache is running (verified)
2. Test `http://localhost/Webdev/quataion/test.php`
3. If test.php works, try `index.html`
4. Check browser console for JavaScript errors
5. Check PHP error logs
