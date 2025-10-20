# Customer Authentication System - Implementation Guide

## Overview

This document describes the customer authentication system that has been implemented for the license management panel. The system allows customers to log in using a randomly generated 5-digit ID and 6-character password.

## System Components

### 1. Database Schema

**New Fields Added to `customers` Table:**
- `customer_id` (VARCHAR(5), UNIQUE) - Random 5-digit customer ID
- `customer_password` (VARCHAR(255)) - Hashed password

**SQL Update Script:** `database_update.sql`

```sql
ALTER TABLE customers 
ADD COLUMN customer_id VARCHAR(5) UNIQUE,
ADD COLUMN customer_password VARCHAR(255);

CREATE INDEX idx_customer_id ON customers(customer_id);
```

### 2. Files Created/Modified

#### New Files:
1. **`customer_login.php`** - Customer login page
2. **`customer_dashboard.php`** - Customer dashboard (after login)
3. **`customer_logout.php`** - Customer logout handler
4. **`database_update.sql`** - Database schema update script

#### Modified Files:
1. **`add_customer.php`** - Updated to generate credentials
2. **`customers.php`** - Updated to display customer IDs in admin panel

## Implementation Details

### Customer Creation Process

When a new customer is added via `add_customer.php`:

1. **Generate Unique 5-Digit ID:**
   ```php
   function generateUniqueCustomerId($mysqli) {
       do {
           $customer_id = str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
           // Check if ID already exists
       } while ($result->num_rows > 0);
       return $customer_id;
   }
   ```

2. **Generate Random 6-Character Password:**
   ```php
   function generateRandomPassword() {
       $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
       $password = '';
       for ($i = 0; $i < 6; $i++) {
           $password .= $characters[rand(0, strlen($characters) - 1)];
       }
       return $password;
   }
   ```

3. **Hash and Store:**
   - Password is hashed using `password_hash()` with `PASSWORD_DEFAULT`
   - Both customer_id and hashed password are stored in database

4. **Display Credentials:**
   - Plain-text credentials are shown to admin once
   - Admin must provide these to the customer
   - Credentials are not stored in plain text

### Customer Login Process

**File:** `customer_login.php`

1. Customer enters their 5-digit ID and password
2. System verifies credentials using `password_verify()`
3. On success, creates session with:
   - `customer_loggedin` = true
   - `customer_id` = database ID
   - `customer_unique_id` = 5-digit customer ID
   - `customer_name` = customer name
4. Redirects to customer dashboard

### Customer Dashboard

**File:** `customer_dashboard.php`

Displays:
- **License Status** - Active/Expired/Cancelled
- **Remaining Days** - Days until license expiration
- **License Type** - Standard/Premium/etc.
- **License Information:**
  - License Key
  - Customer ID
  - Start/End Dates
  - IP Address (if set)
  - HWID (if registered)
- **Customer Information:**
  - Name, Company
  - Email, Phone
  - City/District
  - Address

## Setup Instructions

### Step 1: Update Database Schema

Run the SQL script to add new fields:

```bash
mysql -u oyunmenu -p oyunmenu < panel/database_update.sql
```

Or execute via phpMyAdmin:
1. Open phpMyAdmin
2. Select `oyunmenu` database
3. Go to SQL tab
4. Paste contents of `database_update.sql`
5. Click "Go"

### Step 2: Verify File Permissions

Ensure the following files are accessible:
- `panel/customer_login.php`
- `panel/customer_dashboard.php`
- `panel/customer_logout.php`

### Step 3: Test the System

1. **Create a test customer:**
   - Go to admin panel
   - Click "Müşteri Ekle"
   - Fill in customer details
   - Note the generated credentials

2. **Test customer login:**
   - Navigate to `customer_login.php`
   - Enter the test credentials
   - Verify dashboard displays correctly

3. **Test logout:**
   - Click "Çıkış Yap" in dashboard
   - Verify redirect to login page

## Security Features

1. **Password Hashing:**
   - Uses PHP's `password_hash()` with bcrypt
   - Passwords never stored in plain text
   - Uses `password_verify()` for authentication

2. **Session Management:**
   - Separate session variables for customers vs admins
   - Session-based authentication
   - Automatic session cleanup on logout

3. **Unique ID Generation:**
   - Database check ensures no duplicate IDs
   - 5-digit format (10,000 - 99,999)
   - Index on `customer_id` for fast lookups

4. **SQL Injection Protection:**
   - All queries use prepared statements
   - Input sanitization with `trim()` and `htmlspecialchars()`

## URLs

- **Customer Login:** `https://yourdomain.com/panel/customer_login.php`
- **Customer Dashboard:** `https://yourdomain.com/panel/customer_dashboard.php`
- **Admin Panel:** `https://yourdomain.com/panel/` (existing)

## Admin Panel Updates

The customer list (`customers.php`) now displays:
- Customer ID badge (blue, prominent)
- License key (blurred by default, click to reveal)
- License expiration date
- Status badge (Active/Expired/Cancelled)

## Common Issues & Solutions

### Issue: Database fields not found
**Solution:** Ensure database update script has been run successfully

### Issue: Credentials not displaying after customer creation
**Solution:** Check that success message is not suppressed; credentials shown for 5 seconds

### Issue: Customer can't log in
**Solution:** 
- Verify credentials were copied correctly (case-sensitive)
- Check that customer record exists in database
- Ensure `customer_id` and `customer_password` fields are populated

### Issue: Dashboard shows "Müşteri bilgileri bulunamadı"
**Solution:**
- Verify session is active
- Check that customer has an associated license record
- Ensure database JOIN query is working correctly

## Future Enhancements

Potential improvements to consider:

1. **Password Reset:**
   - Email-based password reset
   - Admin-initiated password reset
   - Temporary password generation

2. **Two-Factor Authentication:**
   - SMS or email verification codes
   - Authenticator app support

3. **Customer Self-Service:**
   - Profile editing
   - Password change
   - License renewal requests

4. **Activity Logging:**
   - Login attempts
   - Dashboard access
   - Profile changes

## Support

For questions or issues:
1. Check this documentation first
2. Review the source code comments
3. Test with a fresh customer account
4. Contact system administrator

## Version History

- **v1.0** (2025-01-21) - Initial implementation
  - Random ID and password generation
  - Customer login system
  - Customer dashboard
  - Admin panel integration

---

**Last Updated:** January 21, 2025
**Author:** System Administrator
