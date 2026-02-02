# Troubleshooting Queue System on Hostinger

## Problem: Emails Not Sending via Queue

### Quick Diagnosis Commands

Run these commands via SSH or Hostinger terminal:

```bash
# 1. Check queue status
php artisan queue:diagnose

# 2. Test if jobs are being added to queue
php artisan queue:test-notification your-email@example.com

# 3. Check for pending jobs
php artisan queue:monitor database

# 4. Check for failed jobs
php artisan queue:failed
```

---

## Common Issues & Solutions

### Issue 1: Jobs Are Added But Not Processed

**Symptoms:**
- `php artisan queue:monitor database` shows pending jobs
- Jobs accumulate in `jobs` table
- Emails never arrive

**Cause:** Cron job not running

**Solution:**

1. **Verify cron job exists in Hostinger hPanel:**
   - Go to Advanced → Cron Jobs
   - Check if your cron job is listed
   - Check "Last Execution" timestamp

2. **Correct cron command:**
   ```bash
   cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
   ```

3. **Verify PHP path:**
   ```bash
   which php
   ```
   Common paths:
   - `/usr/bin/php`
   - `/usr/local/bin/php`
   - `php` (if in PATH)

4. **Test cron command manually:**
   ```bash
   cd /home/u775863429/domains/nemsu-codex.online/public_html
   /usr/bin/php artisan queue:work database --once
   ```

5. **Check cron execution log:**
   - In Hostinger hPanel, check cron job execution history
   - Look for any error messages

---

### Issue 2: Jobs Are Not Being Added to Queue

**Symptoms:**
- `php artisan queue:monitor database` shows 0 jobs
- `jobs` table is always empty
- Even after triggering notifications

**Cause:** Queue connection not set to `database` in production

**Solution:**

1. **Check `.env` file on server:**
   ```bash
   cat .env | grep QUEUE_CONNECTION
   ```
   Should show: `QUEUE_CONNECTION=database`

2. **If it shows `QUEUE_CONNECTION=sync`:**
   ```bash
   # Edit .env file
   nano .env
   
   # Change to:
   QUEUE_CONNECTION=database
   
   # Save and exit
   ```

3. **Clear config cache:**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

4. **Test again:**
   ```bash
   php artisan queue:test-notification your-email@example.com
   php artisan queue:monitor database
   ```

---

### Issue 3: Jobs Fail Immediately

**Symptoms:**
- Jobs appear in `failed_jobs` table
- `php artisan queue:failed` shows errors

**Cause:** Mail configuration error

**Solution:**

1. **Check failed jobs:**
   ```bash
   php artisan queue:failed
   ```

2. **Common errors:**

   **Error: "Connection timeout"**
   ```bash
   # Check mail settings in .env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=587  # Try 465 if 587 doesn't work
   MAIL_ENCRYPTION=tls  # or ssl for port 465
   MAIL_USERNAME=your-email@yourdomain.com
   MAIL_PASSWORD=your-password
   ```

   **Error: "Authentication failed"**
   - Verify email password is correct
   - Check if email account exists in Hostinger
   - Try creating a new email account

3. **Test mail directly:**
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));
   ```

4. **Retry failed jobs:**
   ```bash
   php artisan queue:retry all
   ```

---

### Issue 4: Cron Job Not Executing

**Symptoms:**
- Cron job shows "Never executed" in Hostinger
- "Last Execution" timestamp doesn't update

**Cause:** Incorrect command syntax or permissions

**Solution:**

1. **Check command syntax:**
   - No typos in path
   - Correct username and domain
   - Full path to PHP binary

2. **Test command in SSH:**
   ```bash
   cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once
   ```

3. **Check file permissions:**
   ```bash
   # Make sure artisan is executable
   chmod 644 artisan
   
   # Check storage permissions
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   ```

4. **Simplify command for testing:**
   ```bash
   # Try this simpler version first:
   cd /home/u775863429/domains/nemsu-codex.online/public_html && php artisan queue:work database --once
   ```

5. **Check cron logs:**
   - Hostinger might have a cron log file
   - Check `/var/log/cron` or similar

---

### Issue 5: Mixed Configurations

**Symptoms:**
- Works locally but not on server
- Inconsistent behavior

**Cause:** Different `.env` settings between local and production

**Solution:**

1. **Compare configurations:**
   
   **Local (.env):**
   ```env
   QUEUE_CONNECTION=database
   MAIL_MAILER=smtp  # Should be smtp, not log!
   ```

   **Production (.env):**
   ```env
   QUEUE_CONNECTION=database
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.hostinger.com
   ```

2. **Ensure both use database queue:**
   ```bash
   # On server:
   php artisan config:clear
   php artisan config:cache
   php artisan queue:restart
   ```

---

## Step-by-Step Verification

### Step 1: Verify Environment
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

# Check queue connection
php artisan tinker
>>> config('queue.default')
# Should return: "database"

# Check mail mailer
>>> config('mail.default')
# Should return: "smtp"
```

### Step 2: Test Job Creation
```bash
# Create a test job
php artisan queue:test-notification kcsilagan@nemsu.edu.ph

# Check if job was added
php artisan queue:monitor database
# Should show: 1 job in queue
```

### Step 3: Process Job Manually
```bash
# Process one job
php artisan queue:work database --once

# Check if job was processed
php artisan queue:monitor database
# Should show: 0 jobs in queue

# Check email inbox
```

### Step 4: Verify Cron Job
```bash
# Wait 1-2 minutes after setting up cron
# Create another test job
php artisan queue:test-notification kcsilagan@nemsu.edu.ph

# Wait 1-2 minutes
# Check if job was processed automatically
php artisan queue:monitor database
# Should show: 0 jobs (processed by cron)
```

---

## Emergency: Switch to Sync Mode

If you need emails to work immediately (NOT recommended):

```bash
# Edit .env
nano .env

# Change to:
QUEUE_CONNECTION=sync

# Clear cache
php artisan config:clear
php artisan config:cache
```

**Warning:** This will make your application slower, especially when sending to many students!

---

## Monitoring Commands

```bash
# Check queue status
php artisan queue:monitor database

# Watch queue in real-time
watch -n 5 'php artisan queue:monitor database'

# Check failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Clear all jobs (emergency)
php artisan queue:clear database

# Restart queue workers (after code update)
php artisan queue:restart

# Full diagnostics
php artisan queue:diagnose
```

---

## Logs to Check

1. **Laravel logs:**
   ```bash
   tail -50 storage/logs/laravel.log
   ```

2. **Check for queue errors:**
   ```bash
   grep -i "queue" storage/logs/laravel.log | tail -20
   grep -i "mail" storage/logs/laravel.log | tail -20
   ```

3. **Check for failed jobs:**
   ```bash
   php artisan queue:failed
   ```

---

## Getting Help

When asking for help, provide:

1. **Queue diagnostics:**
   ```bash
   php artisan queue:diagnose > queue-diagnostics.txt
   ```

2. **Environment info:**
   ```bash
   php artisan about > environment-info.txt
   ```

3. **Failed jobs (if any):**
   ```bash
   php artisan queue:failed > failed-jobs.txt
   ```

4. **Recent logs:**
   ```bash
   tail -100 storage/logs/laravel.log > recent-logs.txt
   ```

---

## Quick Fix Checklist

- [ ] `.env` has `QUEUE_CONNECTION=database`
- [ ] `.env` has `MAIL_MAILER=smtp` (not `log`)
- [ ] Mail credentials are correct
- [ ] Database migrations run (`php artisan migrate`)
- [ ] Cron job created in Hostinger hPanel
- [ ] Cron job command is correct
- [ ] Cron job is active (not paused)
- [ ] PHP path is correct (`/usr/bin/php`)
- [ ] File permissions are correct (`775` for storage)
- [ ] Config cache cleared (`php artisan config:clear`)
- [ ] Test job can be created
- [ ] Test job can be processed manually
- [ ] No failed jobs in database
- [ ] Laravel logs show no errors

---

**Last Updated:** January 29, 2026  
**For:** Hostinger Shared Hosting  
**Laravel:** 11+
