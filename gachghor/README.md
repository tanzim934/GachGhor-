# 🌿 GachGhor (গাছঘর) — Setup Guide
## Bangladesh's Online Plant & Gardening eCommerce Platform

---

## 📋 TABLE OF CONTENTS
1. [Requirements](#requirements)
2. [Quick Setup (5 Minutes)](#quick-setup)
3. [Project Structure](#project-structure)
4. [User Accounts & Login](#user-accounts)
5. [Module Overview](#modules)
6. [Security Features](#security)
7. [Customization Guide](#customization)
8. [Troubleshooting](#troubleshooting)

---

## 1. Requirements

| Requirement | Version |
|-------------|---------|
| XAMPP       | 8.0+    |
| PHP         | 8.0+    |
| MySQL       | 5.7+    |
| Web Browser | Chrome, Firefox, Edge |

---

## 2. Quick Setup (5 Minutes)

### Step 1 — Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** (green = running)
3. Start **MySQL** (green = running)

### Step 2 — Copy Project Files
```
Copy the entire "gachghor" folder to:
C:\xampp\htdocs\gachghor\
```
Your structure should be:
```
C:\xampp\htdocs\gachghor\
    ├── index.php
    ├── frontend/
    ├── backend/
    ├── assets/
    └── database/
```

### Step 3 — Create the Database
1. Open your browser → go to: `http://localhost/phpmyadmin`
2. Click **"New"** (left sidebar)
3. Database name: `gachghor`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**
6. Click the **`gachghor`** database you just created
7. Click the **"Import"** tab (top menu)
8. Click **"Choose File"** → select `gachghor/database/gachghor.sql`
9. Click **Go / Import** at the bottom
10. ✅ You should see "Import has been successfully finished"

### Step 4 — Configure Database (if needed)
Open: `backend/includes/config.php`

```php
define('DB_HOST', 'localhost');   // Usually stays as localhost
define('DB_USER', 'root');        // Default XAMPP username
define('DB_PASS', '');            // Default XAMPP has NO password
define('DB_NAME', 'gachghor');    // Database name you created
define('SITE_URL', 'http://localhost/gachghor'); // Your URL
```

> ⚠️ If your MySQL has a password, add it to `DB_PASS`.

### Step 5 — Create Image Folders
The following folders need to exist (and be writable):
```
assets/images/products/   ← Product images
assets/images/blog/       ← Blog post images
```
These are created automatically if PHP has write permission.
If not, create them manually and set permissions to 755.

### Step 6 — Open the Website
```
http://localhost/gachghor
```
You should see the GachGhor homepage! 🎉

---

## 3. Project Structure

```
gachghor/
│
├── index.php                    ← Root redirect to frontend
│
├── database/
│   └── gachghor.sql            ← MySQL database dump
│
├── assets/
│   ├── css/
│   │   └── style.css           ← Main stylesheet (dark mode, responsive)
│   ├── js/
│   │   └── main.js             ← JavaScript (cart AJAX, wishlist, theme)
│   └── images/
│       ├── plant-placeholder.svg
│       ├── products/           ← Upload product images here
│       └── blog/               ← Upload blog images here
│
├── frontend/                    ← Customer-facing pages
│   ├── index.php               ← Homepage (hero, featured, blog)
│   ├── products.php            ← Product listing with filters
│   ├── product.php             ← Product detail page
│   ├── cart.php                ← Shopping cart
│   ├── checkout.php            ← Checkout form
│   ├── order-success.php       ← Order confirmation page
│   ├── orders.php              ← My orders history
│   ├── profile.php             ← User profile management
│   ├── wishlist.php            ← Saved products
│   ├── login.php               ← Login page
│   ├── register.php            ← Registration page
│   ├── forgot-password.php     ← Password reset request
│   ├── contact.php             ← Contact form + FAQ
│   └── blog.php                ← Plant care blog
│
├── backend/
│   ├── includes/
│   │   ├── config.php          ← DB connection, helpers, session
│   │   ├── header.php          ← Shared HTML header + navbar
│   │   └── footer.php          ← Shared footer + mobile nav
│   │
│   ├── api/                    ← AJAX API endpoints
│   │   ├── cart.php            ← Add/update/remove cart items
│   │   ├── wishlist.php        ← Toggle wishlist
│   │   ├── coupon.php          ← Validate and apply coupons
│   │   ├── auth.php            ← Logout
│   │   └── subscribe.php       ← Newsletter
│   │
│   └── admin/                  ← Admin panel pages
│       ├── admin-header.php    ← Admin shared header + sidebar
│       ├── admin-footer.php    ← Admin shared footer
│       ├── dashboard.php       ← Stats, recent orders, alerts
│       ├── products.php        ← List all products
│       ├── product-form.php    ← Add/Edit product
│       ├── orders.php          ← All orders with status update
│       ├── order-detail.php    ← Order details + invoice
│       ├── users.php           ← Customer management
│       ├── coupons.php         ← Discount code management
│       └── reports.php         ← Sales charts + top products
```

---

## 4. User Accounts & Login

### Demo Credentials

| Role     | Email                  | Password |
|----------|------------------------|----------|
| **Admin**    | admin@gachghor.com | password |
| **Customer** | rahim@example.com  | password |
| **Customer** | sumaiya@example.com | password |

> ⚠️ The database stores passwords as **bcrypt hashes** (secure).
> The demo password hash corresponds to `password`.

### Login URLs
- **Customer Login:** `http://localhost/gachghor/frontend/login.php`
- **Admin Panel:** `http://localhost/gachghor/backend/admin/dashboard.php`

---

## 5. Module Overview

### 🛍️ Customer Features
| Feature | File | Description |
|---------|------|-------------|
| Browse Products | `frontend/products.php` | Filter by category, price, sort |
| Product Detail | `frontend/product.php` | Images, care info, reviews |
| Shopping Cart | `frontend/cart.php` | Add/remove/quantity + coupon |
| Checkout | `frontend/checkout.php` | Shipping + COD/Online payment |
| My Orders | `frontend/orders.php` | Order history + status |
| Wishlist | `frontend/wishlist.php` | Saved products |
| Profile | `frontend/profile.php` | Edit info + change password |

### 🔧 Admin Features
| Feature | File | Description |
|---------|------|-------------|
| Dashboard | `admin/dashboard.php` | Overview stats + alerts |
| Products | `admin/products.php` | CRUD + search + filter |
| Add/Edit Product | `admin/product-form.php` | Image upload + all fields |
| Orders | `admin/orders.php` | View + update status inline |
| Order Invoice | `admin/order-detail.php` | Printable invoice |
| Customers | `admin/users.php` | View + block + delete |
| Coupons | `admin/coupons.php` | Create percentage/fixed codes |
| Reports | `admin/reports.php` | Charts + top products |

### 🔌 AJAX API Endpoints
| Endpoint | Method | Purpose |
|----------|--------|---------|
| `api/cart.php` | POST | Add/update/remove cart |
| `api/wishlist.php` | POST | Toggle wishlist |
| `api/coupon.php` | POST | Validate coupon code |
| `api/auth.php?action=logout` | GET | Logout user |

---

## 6. Security Features

| Feature | Implementation |
|---------|---------------|
| **SQL Injection Prevention** | PDO prepared statements throughout |
| **XSS Prevention** | `htmlspecialchars()` on all output |
| **Password Security** | `password_hash()` with bcrypt |
| **Session Security** | PHP native sessions, no token in URL |
| **CSRF** | Form validation + session checks |
| **Admin Access** | `requireAdmin()` on all admin pages |
| **Input Validation** | Frontend (HTML5) + backend (PHP) |
| **File Upload Security** | Extension whitelist + size limit |

---

## 7. Customization Guide

### Change Site Name / Currency
Edit `backend/includes/config.php`:
```php
define('SITE_NAME', 'GachGhor');
define('CURRENCY', '৳');           // Change to $ for USD, etc.
define('SHIPPING_CHARGE', 60);     // Shipping in BDT
define('SITE_URL', 'http://localhost/gachghor');
```

### Change Theme Colors
Edit `assets/css/style.css` (top variables):
```css
:root {
    --gg-green:        #2d7a4f;   /* Main green */
    --gg-green-light:  #4caf78;   /* Lighter green */
    --gg-accent:       #f4a623;   /* Orange accent */
}
```

### Add a New Product Category
1. Go to Admin → (add to DB manually or via `categories.php`)
2. Or run in phpMyAdmin:
```sql
INSERT INTO categories (name, slug, icon, description)
VALUES ('Herbs', 'herbs', '🌿', 'Culinary and medicinal herbs');
```

### Add Product Images
1. Upload images to `assets/images/products/`
2. Recommended size: 800×800px, JPEG/PNG/WebP
3. In Admin → Products → Edit → Upload new image

### Enable Online Payment (Stripe)
1. Sign up at stripe.com
2. Add to checkout.php:
```php
// After requireLogin():
\Stripe\Stripe::setApiKey('sk_test_YOUR_KEY');
```
3. Create payment intent when payment_method = 'online'

---

## 8. Troubleshooting

### ❌ "Database Connection Error"
- Check XAMPP MySQL is running (green)
- Check `DB_NAME` in config.php is `gachghor`
- Make sure you imported the SQL file

### ❌ Blank page / 500 error
- Enable PHP errors: add to config.php:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
- Check XAMPP Apache error log

### ❌ Images not showing
- Create folder: `assets/images/products/`
- Right-click → Properties → make writable
- Upload images via Admin → Edit Product

### ❌ Cart not updating (AJAX not working)
- Check browser console (F12 → Console)
- Verify `SITE_URL` in config.php matches your URL exactly
- Make sure sessions are enabled in PHP

### ❌ Admin panel shows blank
- Confirm you're logged in as admin (role = 'admin')
- Try: `http://localhost/gachghor/frontend/login.php`
- Use: admin@gachghor.com / password

### ❌ File upload fails
- Check PHP `upload_max_filesize` in php.ini (set to 10M)
- Check `post_max_size` in php.ini (set to 20M)
- In XAMPP: PHP → php.ini → search and change these values

---

## 🎨 Design Highlights

- **Mobile-first responsive** with Bootstrap 5
- **Dark mode** toggle with localStorage persistence
- **Bottom nav bar** on mobile (Home, Products, Cart, Profile)
- **Top navbar** with search, categories dropdown on desktop
- **Bangla + English** typography (Hind Siliguri font)
- **Botanical green theme** with organic SVG patterns
- **Smooth animations** on page load and interactions
- **Toast notifications** for cart and wishlist actions

---

## 📞 Need Help?

This project is fully functional and beginner-friendly.
All PHP files have detailed comments explaining each section.

**Technology Stack:**
- Frontend: HTML5, CSS3, Bootstrap 5, Vanilla JavaScript
- Backend: PHP 8 (plain PHP, no framework needed)
- Database: MySQL with PDO
- Icons: Bootstrap Icons
- Charts: Chart.js (admin reports)
- Fonts: Google Fonts (Hind Siliguri + Playfair Display)

---

*Built with 💚 for GachGhor — Bangladesh's Online Plant Store*
