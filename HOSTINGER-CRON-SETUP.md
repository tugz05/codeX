# ✅ HOSTINGER CRON JOB SETUP - PHP FILE METHOD

## 📁 **File Created:**
- **Local:** `C:\laragon\www\codeX\queue-cron.php`
- **Upload to:** `/home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php`

**Note:** Upload this file to the Laravel ROOT directory (where `artisan` is located), NOT inside the `public/` folder!

---

## 🎯 **CRON JOB COMMAND:**

### In Hostinger Cron Jobs Interface:

**Method 1: Use Custom Command (Easiest)**
- **Type:** Custom  
- **Command:**
  ```
  /usr/bin/php /home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
  ```
- **Schedule:** `* * * * *` (Every minute)

**Method 2: Split PHP + Command**
- **Type:** PHP
- **Left field (PHP binary):** `/usr/bin/php`
- **Right field (Script path):**
  ```
  /home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
  ```
- **Schedule:** Every minute (all fields set to `*`)

---

## 📋 **Step-by-Step Setup:**

### 1. **Upload the File**
Upload `queue-cron.php` to your server:
```
/home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
```

### 2. **Test Manually First**
SSH into Hostinger and run:
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php queue-cron.php
```

**Expected output:**
```
[2026-02-03 XX:XX:XX] Starting queue worker...
Processing notifications queue...
INFO  Processing jobs from the [notifications] queue.
Processing: Illuminate\Notifications\SendQueuedNotifications
Processed
Processing default queue...
No jobs available

✓ Queue worker completed successfully!
Execution time: 245.67ms
```

### 3. **Create Cron Job in Hostinger**

Go to **hPanel → Advanced → Cron Jobs**

**Option A: Split Format**
- **Type:** PHP
- **PHP Path:** `/usr/bin/php /home/u775863429/domains/nemsu-codex.online/`
- **Command to run:** `public_html/queue-cron.php`
- **Schedule:** Every minute (`* * * * *`)

**Option B: Custom Format**
- **Type:** Custom
- **Command:**
  ```
  /usr/bin/php /home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
  ```
- **Schedule:** Every minute (`* * * * *`)

### 4. **Save and Wait**
- Cron will run every minute
- Check logs after 2-3 minutes

---

## 🔍 **Verify It's Working:**

### Check Cron Logs:
```bash
tail -20 /home/u775863429/domains/nemsu-codex.online/public_html/storage/logs/cron.log
```

**Expected output:**
```
[2026-02-03 10:30:00] Queue processed
  Notifications queue: exit code 0
  Default queue: exit code 0
  Execution time: 234.56ms

[2026-02-03 10:31:00] Queue processed
  Notifications queue: exit code 0
  Default queue: exit code 0
  Execution time: 189.23ms
```

### Check Queue Status:
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan queue:monitor database
```

**Expected:**
```
[database] database ........................ [0] OK
Pending jobs ................................ 0
```

### Test Email:
1. Create new material/assignment via web
2. Wait 1-2 minutes
3. Check email inbox: `kcsilagan@nemsu.edu.ph`
4. **Email should arrive!**

---

## 📊 **What This Script Does:**

```php
// 1. Processes NOTIFICATIONS queue (emails) first
Artisan::call('queue:work', [
    '--once' => true,
    '--queue' => 'notifications',  // ← Email notifications!
]);

// 2. Then processes DEFAULT queue (messages, etc.)
Artisan::call('queue:work', [
    '--once' => true,
    '--queue' => 'default',  // ← Chat messages!
]);
```

---

## 🆘 **Troubleshooting:**

### If manual test fails:

**Error:** `Class not found` or `Composer autoload error`
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
composer install --no-dev --optimize-autoloader
```

**Error:** `Permission denied`
```bash
chmod 644 queue-cron.php
chmod -R 775 storage
```

**Error:** `Database connection failed`
```bash
# Check .env file exists and has correct DB credentials
cat .env | grep DB_
```

### If cron job doesn't run:

1. **Check cron job is enabled** in Hostinger
2. **Check cron output** (Hostinger might email you errors)
3. **Check logs:**
   ```bash
   ls -la storage/logs/
   cat storage/logs/cron.log
   ```

---

## ✅ **Success Indicators:**

- ✅ `queue-cron.php` uploaded successfully
- ✅ Manual test runs without errors
- ✅ Cron job created and enabled
- ✅ `storage/logs/cron.log` file created
- ✅ Logs show queue processed every minute
- ✅ Queue monitor shows 0 pending jobs
- ✅ **Email arrives within 1-2 minutes** after creating resource

---

## 🎯 **Final Command for Hostinger:**

```
/usr/bin/php /home/u775863429/domains/nemsu-codex.online/public_html/queue-cron.php
```

**Schedule:** `* * * * *` (Every minute)

---

**Upload the file, set up the cron, and test!** 🚀
