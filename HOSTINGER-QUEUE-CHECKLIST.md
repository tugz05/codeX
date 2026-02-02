# 🔍 Hostinger Queue Troubleshooting Checklist

## Your Issue: Mail test works, but notification emails don't send

This means:
- ✅ SMTP credentials are correct
- ✅ Mail server connection works
- ❌ Queue system not processing jobs

---

## 📋 Step-by-Step Troubleshooting

### Step 1: Verify .env Configuration on Server

SSH into your Hostinger server or use File Manager:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
cat .env | grep -E "QUEUE_CONNECTION|MAIL_MAILER"
```

**Should show:**
```
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
```

**If it shows `QUEUE_CONNECTION=sync`:**
1. Edit `.env` file
2. Change to `QUEUE_CONNECTION=database`
3. Run: `php artisan config:clear && php artisan config:cache`

---

### Step 2: Run Diagnostics on Server

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan queue:diagnose
```

**Look for:**
- ✅ "Queue connection is correctly set to 'database'"
- ✅ "Jobs table exists"
- ✅ "Failed jobs table exists"

**If you see errors:**
- "Queue connection should be 'database'" → Fix `.env` and clear cache
- "Database tables not found" → Run `php artisan migrate --force`

---

### Step 3: Test If Jobs Are Being Created

```bash
# Create a test notification
php artisan queue:test-notification kcsilagan@nemsu.edu.ph

# Check queue immediately after
php artisan queue:monitor database
```

**Expected results:**

✅ **If it shows "1 job in queue":**
- Jobs ARE being created correctly
- Problem is: Cron job is not processing them
- → Go to Step 4

❌ **If it shows "0 jobs in queue":**
- Jobs are NOT being created
- Possible causes:
  1. `.env` still has `QUEUE_CONNECTION=sync`
  2. Config cache not cleared
  3. Mail mailer is set to 'log'

**Fix:**
```bash
# Clear cache
php artisan config:clear
php artisan config:cache

# Try again
php artisan queue:test-notification kcsilagan@nemsu.edu.ph
php artisan queue:monitor database
```

---

### Step 4: Test Manual Job Processing

```bash
# Process the queued job manually
php artisan queue:work database --once

# Check if job was processed
php artisan queue:monitor database
```

**Expected results:**

✅ **If queue is now empty (0 jobs):**
- Job processed successfully
- Email should have been sent
- Problem: Cron job is not running
- → Go to Step 5

❌ **If job failed or still in queue:**
```bash
# Check failed jobs
php artisan queue:failed
```
- Read the error message
- Usually mail configuration issue
- Fix mail settings in `.env`

---

### Step 5: Verify Cron Job Configuration

Go to **Hostinger hPanel** → **Advanced** → **Cron Jobs**

**Check these:**

1. **Cron job exists?**
   - [ ] Yes → Continue
   - [ ] No → Create it (see below)

2. **Command is correct?**
   ```bash
   cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
   ```

3. **Schedule is correct?**
   - Minute: `*`
   - Hour: `*`
   - Day: `*`
   - Month: `*`
   - Weekday: `*`
   
   (This means: every minute)

4. **Cron job is active?**
   - Check if it's not paused/disabled

5. **Last Execution timestamp?**
   - Should update every minute
   - If "Never executed" → Problem with command syntax
   - If timestamp is old → Cron might have errors

---

### Step 6: Test Cron Command Manually

SSH into server:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

# Test the exact cron command
/usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90
```

**Expected results:**

✅ **No errors:**
- Command works
- Problem might be with cron scheduling

❌ **Error: "command not found" or "No such file":**
- Wrong PHP path
- Try: `which php` to find correct path
- Common paths: `/usr/bin/php`, `/usr/local/bin/php`, `php`

❌ **Other errors:**
- Read the error message
- Usually permissions or path issues

---

### Step 7: Check for Failed Jobs

```bash
php artisan queue:failed
```

**If you see failed jobs:**
1. Read the error messages
2. Common errors:
   - **"Connection timeout"** → Check mail host/port
   - **"Authentication failed"** → Check mail username/password
   - **"Could not instantiate"** → Missing class/file

3. Fix the underlying issue

4. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

---

## 🎯 Quick Fixes

### Fix 1: Config Cache Issue

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan config:clear
php artisan config:cache
php artisan queue:restart
```

### Fix 2: Recreate Cron Job

1. Delete existing cron job in Hostinger
2. Create new one with this **EXACT** command:
   ```bash
   cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
   ```
3. Schedule: Every minute (`* * * * *`)
4. Save and activate

### Fix 3: Process Queue Manually (Temporary)

While debugging, manually process queue every few minutes:

```bash
# SSH into server and run:
cd /home/u775863429/domains/nemsu-codex.online/public_html
watch -n 60 'php artisan queue:work database --stop-when-empty'
```

(This processes all jobs every 60 seconds)

---

## 📊 What Success Looks Like

### Test Flow:

1. **Trigger notification** (e.g., add student to class)
   ```bash
   # Or manually: php artisan queue:test-notification your@email.com
   ```

2. **Check queue** (immediately after):
   ```bash
   php artisan queue:monitor database
   ```
   **Should show:** 1 job in queue

3. **Wait 1-2 minutes** (for cron to run)

4. **Check queue again**:
   ```bash
   php artisan queue:monitor database
   ```
   **Should show:** 0 jobs (processed by cron)

5. **Check email inbox**
   **Should receive:** Email notification

---

## 🚨 Emergency: If Nothing Works

### Option A: Use Alternative Command

Try simpler cron command:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html && php artisan queue:process-cron --once
```

### Option B: Increase Cron Frequency

Change schedule to every 5 minutes instead:
- Minute: `*/5`
- Others: `*`

### Option C: Temporary Sync Mode (NOT RECOMMENDED)

```bash
# Edit .env
QUEUE_CONNECTION=sync

# Clear cache
php artisan config:clear
```

**Warning:** This will slow down your app!

---

## 📞 Getting More Help

### Provide These Outputs:

1. **Diagnostics:**
   ```bash
   php artisan queue:diagnose
   ```

2. **Queue status:**
   ```bash
   php artisan queue:monitor database
   ```

3. **Failed jobs:**
   ```bash
   php artisan queue:failed
   ```

4. **Recent logs:**
   ```bash
   tail -100 storage/logs/laravel.log
   ```

5. **Cron job screenshot** from Hostinger hPanel

---

## ✅ Final Checklist

- [ ] `.env` has `QUEUE_CONNECTION=database`
- [ ] `.env` has `MAIL_MAILER=smtp`
- [ ] Config cache cleared
- [ ] Test job creates entry in queue
- [ ] Manual processing works (`php artisan queue:work database --once`)
- [ ] Cron job exists in Hostinger
- [ ] Cron job command is correct (no typos)
- [ ] Cron schedule is `* * * * *`
- [ ] Cron job is active (not paused)
- [ ] Cron "Last Execution" updates every minute
- [ ] No failed jobs in database
- [ ] Storage folder has write permissions (775)

---

**Need the full troubleshooting guide?**  
See: `docs/TROUBLESHOOTING-QUEUE-HOSTINGER.md`

**Last Updated:** January 29, 2026
