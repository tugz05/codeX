# 🚀 Quick Cron Job Setup for Hostinger

## Step-by-Step Setup (5 Minutes)

### 1. Access Hostinger Control Panel
- Log in to hPanel
- Go to **Advanced** → **Cron Jobs**
- Click **Create Cron Job**

### 2. Configure Cron Job

**COPY THIS COMMAND** (replace `YOUR_USERNAME` and `YOUR_DOMAIN`):

```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
```

**Example:**
```bash
cd /home/u775863429/domains/nemsu-codex.online/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
```

**Schedule:**
- **Every Minute** (Recommended): `* * * * *`
- **Every 5 Minutes** (Lower load): `*/5 * * * *`

### 3. Alternative: Use Custom Command

```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:process-cron --once > /dev/null 2>&1
```

### 4. Verify in Hostinger Panel

After creating the cron job, you should see:
- ✅ Command listed in Cron Jobs section
- ✅ "Active" status
- ✅ Next run time displayed

### 5. Test It Works

1. Add a student to a class (triggers invitation email)
2. Wait 1-5 minutes (depending on cron schedule)
3. Check if email was received
4. Check `jobs` table in database - should be empty after processing

---

## Common Issues & Quick Fixes

### ❌ Emails not sending?
**Check:**
1. `.env` has correct mail settings
2. Cron job is running (check execution history)
3. No failed jobs: `php artisan queue:failed`

**Fix:**
```bash
# Retry failed jobs
php artisan queue:retry all

# Clear stuck jobs
php artisan queue:clear database
```

### ❌ Can't find PHP path?
**Try these paths:**
- `/usr/bin/php` (most common)
- `/usr/local/bin/php`
- `php` (if in PATH)

**Find your PHP path:**
```bash
which php
```

### ❌ Permission denied?
```bash
chmod +x queue-worker.sh
chmod 644 artisan
```

---

## Quick Commands

```bash
# Check queue status
php artisan queue:monitor database

# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Clear all jobs
php artisan queue:clear database
```

---

## Schedule Options

| Schedule | Cron Syntax | Best For |
|----------|------------|----------|
| Every minute | `* * * * *` | Real-time notifications |
| Every 5 min | `*/5 * * * *` | Balanced performance |
| Every 10 min | `*/10 * * * *` | Low priority emails |
| Every hour | `0 * * * *` | Batch processing |

---

## Hostinger-Specific Notes

✅ **Works on all Hostinger plans** (Shared, Business, Cloud)
✅ **No VPS required** - optimized for shared hosting
✅ **Low resource usage** - uses `--once` flag
⚠️ **Shared hosting limits** - don't run too many concurrent workers

---

## Production Checklist

Before going live:

- [ ] `.env` has `QUEUE_CONNECTION=database`
- [ ] Mail credentials configured in `.env`
- [ ] Database migrations run (`php artisan migrate`)
- [ ] Cron job created in Hostinger panel
- [ ] Test email sent successfully
- [ ] Monitor first 24 hours for failed jobs

---

## Need Help?

- 📚 Full documentation: `docs/hostinger-queue-setup.md`
- 📧 Check Laravel logs: `storage/logs/laravel.log`
- 🔧 Hostinger support for server issues
- 📊 Monitor cron execution in hPanel

---

**Last Updated:** January 2026
**Compatible with:** Laravel 11+, Hostinger Shared/Business/Cloud Hosting
