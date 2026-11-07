# EstateVista India — Production Deployment Guide

This guide provides step-by-step instructions for deploying **EstateVista India** to any PHP 8.1+ compatible hosting environment (Apache, Nginx, cPanel, Plesk, AWS EC2, DigitalOcean, or Docker).

---

## 1. System Requirements

| Component | Minimum Version | Recommended |
|---|---|---|
| **PHP** | 8.1+ | 8.2 or 8.3 |
| **MySQL** | 8.0+ | MySQL 8.0+ or MariaDB 10.4+ |
| **Web Server** | Apache 2.4+ (with `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires`) | Nginx 1.20+ with PHP-FPM |
| **PHP Extensions** | `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `gd` (or `imagick`), `curl` | Default in standard PHP bundles |
| **Character Set** | `utf8mb4` | `utf8mb4_unicode_ci` |

---

## 2. Step-by-Step Deployment Instructions

### Step 1: Upload Files
Upload all codebase files to your web server document root (e.g. `/var/www/html/` or `/home/user/public_html/`):

```bash
# Example using rsync / SSH
rsync -avz --exclude='.git' --exclude='scratch' ./ user@server:/var/www/html/
```

### Step 2: Set File Permissions
Ensure the web server user (`www-data`, `apache`, or `nginx`) owns the upload directories:

```bash
cd /var/www/html

# Set directories to 755 and files to 644
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Make uploads folder writable for property photos & floorplans
chmod -R 775 uploads/
chown -R www-data:www-data uploads/
```

### Step 3: MySQL Database Setup & Import
Create a dedicated MySQL database and import the relational schema and seed data in order:

```bash
# Log into MySQL CLI
mysql -u root -p

# Execute in MySQL:
CREATE DATABASE estatevista_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'estatevista_user'@'localhost' IDENTIFIED BY 'StrongProductionPassword2026!';
GRANT ALL PRIVILEGES ON estatevista_db.* TO 'estatevista_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import the relational schema DDL:
mysql -u estatevista_user -p estatevista_db < database/schema.sql

# Import the initial seed data:
mysql -u estatevista_user -p estatevista_db < database/seed.sql
```

> **Note:** `database/schema.sql` defines all 21 relational tables, composite foreign keys, and indexes. `database/seed.sql` populates the initial luxury residences, cities, amenities, agents, testimonials, and administrative accounts.

---

### Step 4: Environment Configuration (`.env`)
Copy the provided `.env.example` template to `.env` in the root folder:

```bash
cp .env.example .env
```

Edit `.env` with your secure production parameters:

```ini
# Application Environment
APP_ENV=production
APP_DEBUG=false

# Canonical Base URL (Leave empty for dynamic auto-detection)
# SITE_URL=https://www.estatevistaindia.com

# Database Connection
DB_HOST=localhost
DB_PORT=3306
DB_NAME=estatevista_db
DB_USER=estatevista_user
DB_PASS=StrongProductionPassword2026!
DB_CHARSET=utf8mb4

# Concierge Advisory
SITE_EMAIL=concierge@estatevistaindia.com
SITE_PHONE="+91 22 6985 4000"
SITE_WHATSAPP="+91 98200 12345"
RERA_REGISTRATION="MAHARERA Reg. No: A51900038921 | Certified Luxury Advisory"
```

> **Security Rule:** The `.htaccess` file prevents any direct web request from viewing or downloading `.env`. Never commit `.env` with live credentials to version control.

---

## 3. Web Server Configurations

### Option A: Apache Web Server
Make sure `mod_rewrite` and `AllowOverride All` are active in your virtual host configuration:

```apache
<VirtualHost *:80>
    ServerName estatevistaindia.com
    ServerAlias www.estatevistaindia.com
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/estatevista_error.log
    CustomLog ${APACHE_LOG_DIR}/estatevista_access.log combined
</VirtualHost>
```

The bundled `.htaccess` file handles:
- Blocking directory browsing (`Options -Indexes`).
- Blocking access to `.env`, `.git`, `*.sql`, `config/`, and `database/`.
- Blocking PHP execution inside `uploads/`.
- Security HTTP headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection`).
- Gzip/Deflate compression and browser caching.
- Custom `404.php` error routing.

---

### Option B: Nginx Web Server
If running on Nginx with PHP-FPM:

```nginx
server {
    listen 80;
    server_name estatevistaindia.com www.estatevistaindia.com;
    root /var/www/html;
    index index.php index.html;

    # Security Headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Block access to hidden files, environment files, and database dumps
    location ~ /\.(env|git) {
        deny all;
    }
    location ~* \.(sql|log|bak|dist)$ {
        deny all;
    }
    location ^~ /config/ {
        deny all;
    }
    location ^~ /database/ {
        deny all;
    }
    location ^~ /scratch/ {
        deny all;
    }

    # Prohibit script execution in uploads directory
    location ^~ /uploads/ {
        location ~ \.(php|phtml|php3|php4|php5|php7|php8|phps|sh|cgi|pl|py)$ {
            deny all;
        }
    }

    # Route requests
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Custom 404 handler
    error_page 404 /404.php;

    # PHP-FPM FastCGI Handler
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param APP_ENV production;
    }

    # Static Asset Caching
    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico|css|js|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

---

## 4. Default Portals & Administrative Credentials

### Customer / Client Portal (`/login.php` & Header Modal):
- **Email:** `client@estatevistaindia.com`
- **Default Password:** `Client@123`
- **Role:** `client` (Portfolio dashboard, saved properties, scheduled visits, submit property)

### Administrative Command Center (`/admin/login.php`):
- **Super Administrator:**
  - **Email:** `admin@estatevistaindia.com`
  - **Default Password:** `Admin@123`
  - **Access:** Complete system access (Approvals, Users, CRM, CMS, Cities, Settings, Export CSV)
- **Luxury Real Estate Advisor (Agent CRM):**
  - **Email:** `aditya.birla@estatevistaindia.com`
  - **Default Password:** `Agent@123`
  - **Access:** Assigned leads pipeline, follow-up scheduler, client site visits, assigned listings

> **Important:** Immediately log into `/admin/profile.php` after deployment to change passwords.

---

## 5. Post-Deployment Verification

Run the included verification suite via CLI to ensure all 21 tables, endpoints, and security layers are 100% operational:

```bash
# Run Master QA Verification
php scratch/master_qa_suite.php

# Run Full Site Page Render Verification
php scratch/test_full_site_render.php

# Generate / Refresh XML Sitemap
php sitemap.php
```

---

## 6. Security & Performance Checklist

- [x] `APP_ENV=production` set in `.env` (suppresses PHP warning/notice output on public frontend).
- [x] Database credentials defined exclusively in `.env` with restricted file read permissions (`chmod 600 .env`).
- [x] `.htaccess` active with `Options -Indexes` and `Require all denied` on `config/`, `database/`, and `.env`.
- [x] Direct script execution blocked in `uploads/`.
- [x] SSL/TLS active (`https://` enforced via web server or Cloudflare).
- [x] Sitemap linked in `robots.txt` (`/sitemap.xml`).
- [x] Schema.org `RealEstateListing` structured data validated on detail pages.
- [x] Image lazy loading (`loading="lazy"`) and asynchronous decoding active across all cards.
