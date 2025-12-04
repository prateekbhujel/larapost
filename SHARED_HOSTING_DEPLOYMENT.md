# Social Sync - Shared Hosting Deployment Guide

## 🌐 Deploying to Shared Hosting (cPanel, Plesk, etc.)

This guide covers deploying Social Sync to common shared hosting providers like Bluehost, HostGator, SiteGround, etc.

---

## 📋 Prerequisites

- Shared hosting with PHP 8.1+
- SSH access (optional but recommended)
- MySQL or SQLite database
- Composer access (or ability to upload vendor folder)
- Cron job access

---

## 🚀 Method 1: With SSH Access (Recommended)

### Step 1: Prepare Your Server

```bash
# Connect via SSH
ssh username@yourserver.com

# Navigate to web root
cd public_html
# or
cd www
# or wherever your web root is
```

### Step 2: Upload Your Application

**Option A: Git Clone (if Git is available)**
```bash
git clone https://github.com/your-username/crm-social-app.git
cd crm-social-app
```

**Option B: Upload via FTP**
1. Compress your local project: `zip -r crm-social-app.zip crm-social-app/`
2. Upload via FTP/cPanel File Manager
3. Extract on server

### Step 3: Install Dependencies

```bash
cd crm-social-app

# If Composer is available on server
composer install --no-dev --optimize-autoloader

# If Composer is NOT available:
# - Run `composer install` locally
# - Upload the entire vendor folder via FTP
```

### Step 4: Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Edit .env file
nano .env
```

Update these values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Or use SQLite
DB_CONNECTION=sqlite

# Social Sync Credentials
FACEBOOK_APP_ID=your_app_id
FACEBOOK_APP_SECRET=your_secret
# ... add all platform credentials

# Queue
QUEUE_CONNECTION=database
```

### Step 5: Setup Application

```bash
# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 6: Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
# Or use your server's web user
```

### Step 7: Configure Web Server

**For Apache (.htaccess in public directory):**

Already included in Laravel's `public/.htaccess`

**Point domain to public directory:**
- In cPanel: Set document root to `public_html/crm-social-app/public`

### Step 8: Setup Cron Jobs

In cPanel → Cron Jobs, add:

```bash
# Laravel Scheduler (runs every minute)
* * * * * cd /home/username/public_html/crm-social-app && php artisan schedule:run >> /dev/null 2>&1

# Queue Worker (runs every 5 minutes)
*/5 * * * * cd /home/username/public_html/crm-social-app && php artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

### Step 9: Connect Social Accounts

```bash
# Via SSH
php artisan social-sync:add-account facebook

# Or visit in browser
https://yourdomain.com/social-sync/connect/facebook
```

---

## 🔧 Method 2: Without SSH Access (FTP Only)

### Step 1: Prepare Locally

```bash
# On your local machine
cd crm-social-app

# Install dependencies
composer install --no-dev --optimize-autoloader

# Build assets if needed
npm install && npm run build

# Configure .env for production
cp .env.example .env
# Edit .env with production values

# Generate key locally
php artisan key:generate

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 2: Upload via FTP

Using FileZilla or cPanel File Manager:

1. **Upload entire project** to `public_html/crm-social-app/`
2. **DO NOT upload** these folders/files:
   - `node_modules/`
   - `.git/`
   - `.env.example`
   - `tests/`

### Step 3: Setup Database via cPanel

1. **Create MySQL Database**
   - cPanel → MySQL Databases
   - Create new database: `username_social_crm`
   - Create user and add to database
   - Grant all privileges

2. **Import migrations manually** (if needed):
   - Go to phpMyAdmin
   - Select your database
   - Import SQL file or run migrations via web interface

### Step 4: Run Migrations via Web

Create `setup.php` in your `public` folder:

```php
<?php
// setup.php - REMOVE THIS FILE AFTER SETUP!

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Run migrations
$kernel->call('migrate', ['--force' => true]);

// Install Social Sync
$kernel->call('social-sync:install');

echo "Setup complete! DELETE THIS FILE NOW!";
```

Visit: `https://yourdomain.com/setup.php`

**IMPORTANT: Delete setup.php after running!**

### Step 5: Configure Document Root

In cPanel:
1. Go to **Domains** or **Addon Domains**
2. Set document root to: `public_html/crm-social-app/public`

### Step 6: Setup Cron Jobs

In cPanel → Cron Jobs:

```bash
# Command (adjust path):
* * * * * /usr/bin/php /home/username/public_html/crm-social-app/artisan schedule:run
*/5 * * * * /usr/bin/php /home/username/public_html/crm-social-app/artisan queue:work --stop-when-empty
```

---

## 🔐 Security Configuration

### 1. Protect Sensitive Files

Create/update `.htaccess` in project root:

```apache
# Prevent access to sensitive files
<FilesMatch "^\.env">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "composer\.(json|lock)">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 2. Force HTTPS

In `public/.htaccess`:

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. Secure Credentials

```env
# In .env
APP_DEBUG=false
APP_ENV=production

# Use strong encryption
# Never commit .env to Git
```

---

## 📊 Performance Optimization

### 1. Enable OPcache

In cPanel → Select PHP Version → Options:
- Enable `opcache`
- Set `opcache.memory_consumption=128`
- Set `opcache.max_accelerated_files=10000`

### 2. Laravel Optimizations

```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Database Optimizations

For SQLite:
```env
DB_CONNECTION=sqlite
# Faster for small apps on shared hosting
```

For MySQL:
- Add indexes to `scheduled_posts` table
- Use connection pooling

---

## 🔄 Queue Configuration for Shared Hosting

### Option 1: Cron-Based Queue (Recommended)

```bash
# Run every 5 minutes
*/5 * * * * php /path/to/artisan queue:work --stop-when-empty --tries=3 --timeout=300
```

### Option 2: Sync Queue (No Background Processing)

```env
# In .env
QUEUE_CONNECTION=sync
```

Posts will be published immediately (no background processing).

### Option 3: Database Queue with Supervisor (VPS only)

Not available on most shared hosting.

---

## 🌐 Update OAuth Redirect URLs

Update in your social platform apps:

**Development:**
- `http://localhost:8000/social-sync/callback/{platform}`

**Production:**
- `https://yourdomain.com/social-sync/callback/{platform}`

Update in:
- Facebook Developer Console
- Twitter Developer Portal
- LinkedIn Developer Portal

---

## 🧪 Testing on Production

### 1. Test Basic Functionality

```bash
# Via SSH
php artisan social-sync:list-accounts
php artisan social-sync:test

# Via browser
Visit: https://yourdomain.com/social-test
```

### 2. Test Scheduled Posts

```php
// Create a test post scheduled for 5 minutes from now
SocialMedia::post()
    ->content('Test scheduled post')
    ->scheduleFor(now()->addMinutes(5))
    ->platforms(['facebook'])
    ->publish();

// Check cron is running:
// Wait 5 minutes, check posts table
```

### 3. Monitor Logs

```bash
# View logs
tail -f storage/logs/laravel.log

# Or download via FTP and check locally
```

---

## 🐛 Common Shared Hosting Issues

### Issue 1: "Class not found"

**Solution:**
```bash
composer dump-autoload --optimize
php artisan clear-compiled
```

### Issue 2: Permission Denied

**Solution:**
```bash
chmod -R 755 storage bootstrap/cache
```

Or in cPanel File Manager:
- Select `storage` folder → Change Permissions → 755

### Issue 3: Queue Not Processing

**Solution:**
Check cron jobs are running:
```bash
# Add email notification to cron
*/5 * * * * php /path/to/artisan queue:work --stop-when-empty 2>&1 | mail -s "Queue Output" your@email.com
```

### Issue 4: 500 Internal Server Error

**Solution:**
1. Check error logs: cPanel → Error Log
2. Enable debug temporarily:
   ```env
   APP_DEBUG=true
   ```
3. Check file permissions
4. Clear cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Issue 5: Database Connection Error

**Solution:**
- Verify database credentials in `.env`
- Check database user has proper permissions
- Ensure database exists
- Try using `127.0.0.1` instead of `localhost`

---

## 📈 Monitoring & Maintenance

### 1. Setup Error Monitoring

Use Laravel's built-in error logging:

```php
// config/logging.php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

### 2. Monitor Queue

Create a monitoring route:

```php
// routes/web.php
Route::get('/queue-status', function () {
    $pending = \SocialSync\Models\ScheduledPost::pending()->count();
    $failed = \SocialSync\Models\ScheduledPost::failed()->count();

    return response()->json([
        'pending' => $pending,
        'failed' => $failed,
        'queue_connection' => config('queue.default'),
    ]);
})->middleware('auth'); // Protect this route!
```

### 3. Database Backups

Setup automatic backups in cPanel:
- cPanel → Backup → Schedule automatic backups

### 4. Update Application

```bash
# Via SSH
cd /path/to/app
git pull
composer install --no-dev
php artisan migrate --force
php artisan optimize
```

---

## 📞 Provider-Specific Notes

### Bluehost
- PHP version: Select 8.1+ in cPanel
- Document root: `public_html/app-name/public`
- Cron: Full path required

### HostGator
- Similar to Bluehost
- Use `php74` or `php81` in cron commands

### SiteGround
- Great PHP support
- Built-in Git deployment available
- Easy cron job setup

### GoDaddy
- May need to use `/usr/bin/php` in cron
- Sometimes slower queue processing

---

## ✅ Deployment Checklist

- [ ] Code uploaded to server
- [ ] Dependencies installed
- [ ] .env configured for production
- [ ] Database created and migrated
- [ ] Permissions set correctly
- [ ] Document root pointed to public/
- [ ] Cron jobs configured
- [ ] HTTPS enabled
- [ ] Social accounts connected
- [ ] Test post successful
- [ ] Scheduled posts working
- [ ] Error logging enabled
- [ ] Backups configured

---

## 🎉 Success!

Your Social Sync package is now live on shared hosting! You can:

✅ Post to multiple platforms
✅ Schedule content
✅ Manage multiple accounts
✅ Process posts via cron

**Remember:** Check logs regularly and monitor queue processing!

---

## 💡 Pro Tips

1. **Use SQLite on shared hosting** - Often faster than MySQL
2. **Keep vendor folder** - Don't delete, it's needed
3. **Test cron jobs** - Make sure they run every 5 minutes
4. **Monitor storage** - Clear old logs regularly
5. **Use CDN for media** - Upload images to external CDN

**Need help?** Check `COMPLETE_INSTALLATION_GUIDE.md` for more details!
