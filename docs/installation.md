---
title: VPS Installation Guide
---

# VPS Installation Guide

This guide walks you through installing Genius SMS on a Linux VPS (Ubuntu 22.04 or 24.04 recommended).

---

## Server Requirements

Before you begin, make sure your VPS meets these requirements:

| Requirement | Minimum |
|---|---|
| OS | Ubuntu 22.04 / 24.04 LTS |
| RAM | 2 GB (4 GB recommended) |
| Disk | 20 GB free space |
| PHP | 8.3 |
| MySQL | 8.0 |
| Redis | 6+ |
| Node.js | 20+ |
| Nginx | latest |
| Composer | 2+ |

---

## Step 1 — Update the Server

Log into your VPS via SSH and update the system:

```bash
sudo apt update && sudo apt upgrade -y
```

---

## Step 2 — Install PHP 8.3

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-mysql \
  php8.3-redis php8.3-xml php8.3-mbstring php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl php8.3-tokenizer
```

Verify:

```bash
php -v
```

---

## Step 3 — Install MySQL 8

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

Create a database and user for the application:

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE genius_sms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'genius_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON genius_sms.* TO 'genius_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Step 4 — Install Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

## Step 5 — Install Node.js and Composer

**Node.js 20:**

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
```

**Composer:**

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

---

## Step 6 — Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

---

## Step 7 — Clone the Application

Navigate to the web root and clone the repository:

```bash
cd /var/www
sudo git clone https://github.com/xgenious/genius-sms.git genius-sms
sudo chown -R $USER:www-data /var/www/genius-sms
```

---

## Step 8 — Install PHP Dependencies

```bash
cd /var/www/genius-sms
composer install --no-dev --optimize-autoloader
```

---

## Step 9 — Configure Environment

Copy the example environment file:

```bash
cp .env.example .env
```

Open it for editing:

```bash
nano .env
```

Update these key values:

```env
APP_NAME="Genius SMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=genius_sms
DB_USERNAME=genius_user
DB_PASSWORD=your_strong_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com

SUPER_ADMIN_EMAIL=admin@yourdomain.com
SUPER_ADMIN_PASSWORD=your_admin_password
```

Save and close (`Ctrl+X`, then `Y`).

---

## Step 10 — Generate Application Key

```bash
php artisan key:generate
```

---

## Step 11 — Run Database Migrations & Seeders

```bash
php artisan migrate --force
php artisan db:seed --force
```

This creates all tables and seeds the initial super admin account using the `SUPER_ADMIN_EMAIL` and `SUPER_ADMIN_PASSWORD` values from your `.env`.

---

## Step 12 — Build Frontend Assets

```bash
npm install
npm run build
```

---

## Step 13 — Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/genius-sms/storage
sudo chown -R www-data:www-data /var/www/genius-sms/bootstrap/cache
sudo chmod -R 775 /var/www/genius-sms/storage
sudo chmod -R 775 /var/www/genius-sms/bootstrap/cache
```

---

## Step 14 — Configure Nginx

Create a new Nginx site configuration:

```bash
sudo nano /etc/nginx/sites-available/genius-sms
```

Paste the following (replace `yourdomain.com` with your actual domain):

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/genius-sms/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and reload Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/genius-sms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Step 15 — Install SSL Certificate (HTTPS)

Use Certbot for a free SSL certificate:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

Follow the prompts. Certbot will automatically update your Nginx config for HTTPS.

---

## Step 16 — Set Up the Queue Worker (Laravel Horizon)

Horizon manages background jobs (PDF generation, emails, SMS blasts).

Install Supervisor to keep Horizon running:

```bash
sudo apt install -y supervisor
```

Create a Supervisor config:

```bash
sudo nano /etc/supervisor/conf.d/genius-sms-horizon.conf
```

```ini
[program:genius-sms-horizon]
process_name=%(program_name)s
command=php /var/www/genius-sms/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/genius-sms/storage/logs/horizon.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start genius-sms-horizon
```

---

## Step 17 — Set Up the Laravel Scheduler

The scheduler handles recurring tasks (attendance reminders, report generation, etc.).

Add a cron job:

```bash
sudo crontab -e -u www-data
```

Add this line:

```
* * * * * php /var/www/genius-sms/artisan schedule:run >> /dev/null 2>&1
```

---

## Step 18 — Optimise for Production

Run these commands to cache routes, config, and views for better performance:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

---

## First Login

Open your browser and go to `https://yourdomain.com`.

Log in with the super admin credentials you set in `.env`:

- **Email:** value of `SUPER_ADMIN_EMAIL`
- **Password:** value of `SUPER_ADMIN_PASSWORD`

From the super admin panel you can create schools and assign school admins.

---

## Updating the Application

When a new version is released:

```bash
cd /var/www/genius-sms

git pull origin main

composer install --no-dev --optimize-autoloader
npm install && npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo supervisorctl restart genius-sms-horizon
```

---

## Troubleshooting

### Blank page or 500 error
Check the Laravel log:
```bash
tail -f /var/www/genius-sms/storage/logs/laravel.log
```

### Permission denied errors
```bash
sudo chown -R www-data:www-data /var/www/genius-sms/storage
sudo chmod -R 775 /var/www/genius-sms/storage
```

### Nginx 502 Bad Gateway
Make sure PHP-FPM is running:
```bash
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm
```

### Queue jobs not processing
Check Horizon status:
```bash
sudo supervisorctl status genius-sms-horizon
sudo supervisorctl restart genius-sms-horizon
```
