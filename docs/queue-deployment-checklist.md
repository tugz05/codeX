# Queue System Deployment Checklist for Hostinger

Use this checklist when deploying to Hostinger to ensure the queue system is properly configured.

## Pre-Deployment

- [ ] All code committed to repository
- [ ] Database migrations are up to date
- [ ] `.env.example` updated with queue settings
- [ ] Documentation reviewed

## Deployment Steps

### 1. Upload Files to Hostinger

- [ ] Upload all application files to `public_html`
- [ ] Ensure `queue-worker.sh` is uploaded
- [ ] Ensure `.env` file is configured (or copy from `.env.example`)

### 2. Configure Environment Variables

Edit `.env` file on server and set:

```env
# Queue Configuration
QUEUE_CONNECTION=database
DB_QUEUE_TABLE=jobs
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=90

# Mail Configuration (Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yourdomain.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"
```

- [ ] Queue connection set to `database`
- [ ] Mail settings configured with Hostinger credentials
- [ ] Mail from address set correctly

### 3. Run Database Migrations

Via SSH or Hostinger terminal:

```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html
php artisan migrate --force
```

Verify these tables exist:
- [ ] `jobs`
- [ ] `job_batches`
- [ ] `failed_jobs`

### 4. Set File Permissions (via SSH)

```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html
chmod +x queue-worker.sh
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

- [ ] `queue-worker.sh` is executable
- [ ] Storage directory is writable
- [ ] Cache directory is writable

### 5. Test Queue System

```bash
# Test email sending
php artisan queue:test your-email@example.com

# Process the test job
php artisan queue:work database --once

# Check queue status
php artisan queue:monitor database
```

- [ ] Test email command runs without errors
- [ ] Queue worker processes jobs successfully
- [ ] Test email received

### 6. Create Cron Job in Hostinger

1. Log in to Hostinger hPanel
2. Go to **Advanced** → **Cron Jobs**
3. Click **Create Cron Job**

**Configuration:**

- **Common Settings:** Custom
- **Minute:** `*` (or `*/5` for every 5 minutes)
- **Hour:** `*`
- **Day:** `*`
- **Month:** `*`
- **Weekday:** `*`

**Command:**
```bash
cd /home/YOUR_USERNAME/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work database --once --queue=notifications,default --tries=3 --timeout=90 > /dev/null 2>&1
```

**Replace:**
- `YOUR_USERNAME` with your Hostinger username
- `YOUR_DOMAIN` with your domain name

- [ ] Cron job created successfully
- [ ] Command syntax verified
- [ ] Schedule set correctly
- [ ] Cron job is active

### 7. Verify Cron Job Execution

Wait 1-5 minutes, then check:

- [ ] Cron job has "Last Execution" timestamp in hPanel
- [ ] No errors in execution log
- [ ] Jobs processed (check `jobs` table in database)

### 8. Test End-to-End

Perform actions that trigger emails:

1. Add a student to a class (sends invitation)
2. Grade an assignment and return to student
3. Create a new quiz/assignment

- [ ] Jobs added to queue
- [ ] Jobs processed by cron worker
- [ ] Emails received successfully
- [ ] No failed jobs in database

### 9. Monitor and Verify

```bash
# Check failed jobs
php artisan queue:failed

# Monitor queue in real-time
php artisan queue:monitor database

# Check logs
tail -f storage/logs/laravel.log
```

- [ ] No failed jobs
- [ ] Queue processing regularly
- [ ] No errors in logs
- [ ] Email delivery working

## Post-Deployment Monitoring

### First 24 Hours

- [ ] Monitor failed jobs: `php artisan queue:failed`
- [ ] Check cron execution history in hPanel
- [ ] Verify email delivery rate
- [ ] Monitor server resource usage
- [ ] Check Laravel logs for errors

### First Week

- [ ] Review failed jobs weekly
- [ ] Adjust cron frequency if needed
- [ ] Monitor email bounce rates
- [ ] Optimize queue performance if needed

### Ongoing

- [ ] Set up alerts for failed jobs
- [ ] Regular cleanup of old failed jobs
- [ ] Monitor queue depth during peak times
- [ ] Review server logs monthly

## Troubleshooting Common Issues

### ❌ Cron job not executing

- [ ] Verify PHP path: `/usr/bin/php` (try `which php` via SSH)
- [ ] Check command syntax (no typos in path)
- [ ] Verify file permissions
- [ ] Check Hostinger cron job execution log

### ❌ Jobs stuck in queue

- [ ] Check for failed jobs: `php artisan queue:failed`
- [ ] Retry failed jobs: `php artisan queue:retry all`
- [ ] Clear stuck jobs: `php artisan queue:clear database`
- [ ] Verify cron job is running

### ❌ Emails not sending

- [ ] Verify mail credentials in `.env`
- [ ] Test mail config: `php artisan queue:test your-email@example.com`
- [ ] Check Hostinger email logs
- [ ] Verify SMTP settings (port 587, TLS)

### ❌ High resource usage

- [ ] Reduce cron frequency (every 5 minutes instead of 1)
- [ ] Use `--once` flag instead of `--stop-when-empty`
- [ ] Monitor via Hostinger resource usage dashboard
- [ ] Consider upgrading to VPS if needed

## Rollback Plan

If queue system causes issues:

1. **Disable cron job** in Hostinger hPanel
2. **Switch to sync queue:**
   ```bash
   # In .env
   QUEUE_CONNECTION=sync
   ```
3. **Clear queue:**
   ```bash
   php artisan queue:clear database
   ```
4. **Investigate and fix issues**
5. **Re-enable when ready**

## Support Resources

- **Quick Reference:** `docs/CRON-SETUP-QUICK-REFERENCE.md`
- **Full Documentation:** `docs/hostinger-queue-setup.md`
- **Laravel Queue Docs:** https://laravel.com/docs/queues
- **Hostinger Support:** https://www.hostinger.com/contact

## Sign-Off

- [ ] All checklist items completed
- [ ] Queue system tested and working
- [ ] Documentation updated
- [ ] Team notified of deployment

**Deployed By:** _______________  
**Date:** _______________  
**Environment:** Production / Staging  
**Notes:** _______________

---

**Status:** Ready for deployment ✅  
**Last Updated:** January 2026  
**Version:** 1.0
