# Deployment Guide - Quotation Management System

## ✅ Security Enhancements Implemented

### 1. **SQL Injection Protection**

- All database queries use prepared statements
- Input validation and sanitization on all user inputs
- Parameterized queries throughout the application

### 2. **XSS (Cross-Site Scripting) Protection**

- All output uses `htmlspecialchars()` for escaping
- Input sanitization functions in `config.php`
- Content Security Policy ready (can be added to headers)

### 3. **CSRF (Cross-Site Request Forgery) Protection**

- CSRF tokens implemented on all forms
- Token validation before processing sensitive actions
- Session-based token generation

### 4. **Session Security**

- Secure session cookie settings (HttpOnly, SameSite)
- Session regeneration on login
- Authentication checks on all protected pages

### 5. **Input Validation**

- Email validation using `filter_var()`
- GSTIN format validation
- File upload validation (size, type)
- Rate limiting helper function

### 6. **Password Security**

- Passwords hashed using `password_hash()` with PASSWORD_DEFAULT
- Password verification using `password_verify()`
- Minimum password length enforcement

## 🎯 Trial System Implementation

### Features:

1. **Trial Landing Page** (`trial.php`)

   - Beautiful landing page with "Start Free Trial" button
   - Shows trial features and limits
   - Redirects to registration

2. **Trial Limits:**

   - **2 Instruments** (products)
   - **2 Companies**
   - **2 Quotations**

3. **Trial Enforcement:**

   - Limits checked before adding instruments
   - Limits checked before adding companies
   - Limits checked before creating quotations
   - Automatic upgrade prompts when limits reached

4. **Trial Status Display:**
   - Trial banner showing remaining counts
   - Upgrade links prominently displayed
   - Clear messaging about trial limitations

## 💎 Premium Plans

### Available Plans:

1. **Monthly Plan** - ₹999/month
2. **Yearly Plan** - ₹9999/year (17% savings) ⭐ MOST POPULAR
3. **3-Yearly Plan** - ₹24999/3 years (32% savings)

### Premium Features:

- Unlimited instruments
- Unlimited companies
- Unlimited quotations
- Priority support
- All premium features

## 📋 Database Setup

### Step 1: Run Database Migration

Execute the SQL file `database_migration.sql` on your database:

```sql
-- This adds subscription and trial tracking fields to users table
ALTER TABLE users
ADD COLUMN IF NOT EXISTS subscription_type ENUM('trial', 'monthly', 'yearly', '3yearly') DEFAULT 'trial',
ADD COLUMN IF NOT EXISTS subscription_start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS subscription_end_date DATETIME NULL,
ADD COLUMN IF NOT EXISTS trial_products_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS trial_companies_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS trial_quotations_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE;
```

### Step 2: Update Existing Users

```sql
UPDATE users SET subscription_type = 'trial' WHERE subscription_type IS NULL;
UPDATE users SET
    trial_products_count = 0,
    trial_companies_count = 0,
    trial_quotations_count = 0
WHERE subscription_type = 'trial';
```

## 🚀 Deployment Steps

### 1. **Upload Files**

Upload all files to your web server maintaining the directory structure.

### 2. **Database Configuration**

- Update `config.php` with your database credentials
- Ensure database migration is run

### 3. **File Permissions**

```bash
chmod 644 *.php *.html *.css
chmod 755 ./
```

### 4. **Security Headers (Optional but Recommended)**

Add to your `.htaccess` or server config:

```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "DENY"
Header set X-XSS-Protection "1; mode=block"
Header set Referrer-Policy "strict-origin-when-cross-origin"
```

### 5. **HTTPS Setup**

- Ensure HTTPS is enabled for production
- Update session cookie security in `config.php` if needed

## 📁 File Structure

```
quataion/
├── config.php              # Core configuration & security functions
├── trial.php               # Trial landing page
├── premium.php             # Premium plans page
├── login.php               # Login (redirects logged-in users)
├── register.php            # Registration (initializes trial)
├── home.php                # Main dashboard
├── form2.php               # Quotation form (with trial checks)
├── add-Product.php         # Add instrument (with trial checks)
├── add-company.php         # Add company (with trial checks)
├── database_migration.sql  # Database setup SQL
└── index.html              # Homepage (links to trial)
```

## 🔐 Security Checklist

- [x] SQL Injection protection (prepared statements)
- [x] XSS protection (output escaping)
- [x] CSRF protection (tokens on forms)
- [x] Session security (secure cookies)
- [x] Input validation (email, GSTIN, etc.)
- [x] Password hashing (bcrypt)
- [x] File upload validation
- [x] Authentication checks
- [x] Rate limiting helper

## 🎨 User Flow

1. **New User:**

   - Visits homepage → Clicks "Start Free Trial"
   - Fills registration form → Account created with trial status
   - Can add 2 instruments, 2 companies, create 2 quotations
   - When limit reached → Prompted to upgrade

2. **Existing User (Logged In):**

   - Visits login page → Redirected to home.php
   - Can use features based on subscription type

3. **Upgrade Flow:**
   - User clicks upgrade link → Goes to premium.php
   - Selects plan → Subscription updated
   - Returns to app with premium access

## 🐛 Troubleshooting

### Issue: Trial limits not working

- Check database migration was run
- Verify `subscription_type` column exists
- Check `trial_*_count` columns are initialized

### Issue: CSRF errors

- Ensure sessions are working
- Check `csrf_token()` function is called before form submission
- Verify token validation in form handlers

### Issue: Users can't upgrade

- Check `update_subscription()` function
- Verify database connection
- Check subscription type enum values match

## 📝 Notes

- All new users are automatically set to trial
- Trial counts are tracked per user
- Premium users have unlimited access
- Subscription expiration is checked automatically
- All security functions are in `config.php` for easy maintenance

## 🔄 Future Enhancements

1. Payment gateway integration (Stripe, PayPal, etc.)
2. Email notifications for trial expiration
3. Admin dashboard for subscription management
4. Analytics and reporting
5. API rate limiting middleware
