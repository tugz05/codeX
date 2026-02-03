# 🚨 URGENT: Cron Job Command Fix

## ❌ Current Problem

Your cron job command in the screenshot is **INCORRECT**. It shows:

```bash
/usr/bin/php /home/u775863429/domains/nemsu-codex.online/public_html/php artisan queue:work...
```

This is trying to run PHP on `/home/u775863429/domains/nemsu-codex.online/public_html/php` which doesn't exist!

---

## ✅ CORRECT Command

**Delete the current cron job and create a new one with this EXACT command:**

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 >> storage/logs/cron.log 2>&1
```

---

## 📋 Step-by-Step Fix

### 1. Delete Current Cron Job
Click the **"Delete"** button next to your current cron job in Hostinger.

### 2. Create New Cron Job

**Go to:** Hostinger hPanel → Advanced → Cron Jobs → Create Cron Job

**Command to run:**
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 >> storage/logs/cron.log 2>&1
```

**Schedule:**
- Minute: `*`
- Hour: `*`
- Day: `*`
- Month: `*`
- Weekday: `*`

### 3. Save

Click **"Save"** button.

---

## 🧪 Verify It Works

### Step 1: Check Cron Output (After 2 minutes)

Click **"View Output"** on your cron job.

**Should show something like:**
```
[2024-02-02 10:15:01][1] Processing: Illuminate\Notifications\...
[2024-02-02 10:15:02][1] Processed:  Illuminate\Notifications\...
```

**Should NOT show:**
- "command not found"
- "No such file or directory"
- Empty output

### Step 2: Test Via SSH

```bash
# 1. Create a test notification
cd /home/u775863429/domains/nemsu-codex.online/public_html
php artisan queue:test kcsilagan@nemsu.edu.ph

# 2. Check if job was created
php artisan queue:monitor database
# Should show: 1 job in queue

# 3. Wait 1-2 minutes for cron to process

# 4. Check again
php artisan queue:monitor database
# Should show: 0 jobs (processed by cron)

# 5. Check email inbox
# Should have received test email
```

---

## 🔍 Common Issues & Solutions

### Issue 1: "command not found"

**Cause:** Wrong PHP path

**Solution:** Try finding correct PHP path:
```bash
which php
# Or
whereis php
```

Then use that path in the cron command. Common paths:
- `/usr/bin/php`
- `/usr/local/bin/php`
- `/opt/cpanel/ea-php82/root/usr/bin/php`

### Issue 2: "No such file or directory"

**Cause:** Incorrect project path

**Solution:** Verify your project path:
```bash
ls -la /home/u775863429/domains/nemsu-codex.online/public_html/artisan
```

If artisan doesn't exist at that path, find the correct path.

### Issue 3: "Permission denied"

**Cause:** File permissions

**Solution:**
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html
chmod 644 artisan
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Issue 4: Empty Output

**Cause:** 
- Cron not running
- Wrong schedule
- Command syntax error

**Solution:**
1. Check cron job is **Active** (not paused)
2. Verify schedule is `* * * * *`
3. Double-check command has NO typos
4. Wait 2-3 minutes and check "Last Execution" timestamp

---

## 📊 What Should Happen

### Correct Flow:

1. **You create an assignment/quiz** → Job added to database
2. **Cron runs (every minute)** → Picks up the job
3. **Job processes** → Email sent
4. **Student receives email** → Within 1-5 minutes ✅

### Current (Broken) Flow:

1. **You create an assignment/quiz** → Job added to database
2. **Cron tries to run** → ❌ Command fails (wrong syntax)
3. **Job stays in queue** → Never processed
4. **No email sent** → ❌

---

## 🎯 Quick Test

**After fixing the cron command:**

```bash
# 1. SSH into server
cd /home/u775863429/domains/nemsu-codex.online/public_html

# 2. Check current jobs
php artisan queue:monitor database

# 3. If there are stuck jobs, process them manually
php artisan queue:work database --stop-when-empty

# 4. Create new test
php artisan queue:test kcsilagan@nemsu.edu.ph

# 5. Wait 2 minutes

# 6. Check if auto-processed
php artisan queue:monitor database
# Should be 0 (cron processed it)

# 7. Check cron output
cat storage/logs/cron.log
# Should show processing messages
```

---

## ✅ Success Indicators

**Cron is working when:**
- ✅ "Last Execution" updates every minute
- ✅ "View Output" shows processing messages
- ✅ `php artisan queue:monitor database` shows 0 jobs
- ✅ Emails arrive within 1-5 minutes
- ✅ `storage/logs/cron.log` shows activity

**Cron is NOT working when:**
- ❌ "Last Execution" never updates
- ❌ "View Output" shows errors or empty
- ❌ Jobs accumulate in queue
- ❌ No emails sent
- ❌ Log file empty or errors

---

## 🆘 If Still Not Working

Run full diagnostics:

```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html

# 1. Check config
php artisan tinker
>>> config('queue.default')
# Should be: "database"
>>> config('mail.default')  
# Should be: "smtp"
>>> exit

# 2. Check database
php artisan queue:monitor database

# 3. Check failed jobs
php artisan queue:failed

# 4. Try manual processing
php artisan queue:work database --once

# 5. Check logs
tail -50 storage/logs/laravel.log
tail -50 storage/logs/cron.log
```

Send me the output of these commands if still not working.

---

**Last Updated:** February 2, 2026
